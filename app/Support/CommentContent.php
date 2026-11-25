<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentContent
{
    public static function validate(Request $request): void
    {
        $request->validate([
            'description' => ['nullable', 'string', 'max:10000'],
            'comment_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,mp3,wav', 'max:5120'],
            'voice_note' => ['nullable', 'string', 'max:15000000'],
            'is_recording' => ['nullable', 'boolean'],
        ]);
        if ($request->boolean('is_recording')) {
            throw ValidationException::withMessages(['voice_note' => 'Stop recording and wait for the voice note to finish processing before sending.']);
        }
        if (trim((string) $request->input('description', '')) === '' && !$request->hasFile('comment_attachment') && !$request->filled('voice_note')) {
            throw ValidationException::withMessages(['description' => 'Enter a comment, attach a file, or record a voice note before sending.']);
        }
        if ($request->filled('voice_note')) self::audio($request->input('voice_note'));
    }

    public static function audio(string $value): array
    {
        $extension = 'wav';
        if (str_starts_with($value, 'data:')) {
            if (!preg_match('~^data:audio/(webm|ogg|mp4|mpeg|mp3|wav|x-wav)(?:;codecs=[a-zA-Z0-9.,_-]+)?;base64,(.+)$~s', $value, $matches)) {
                throw ValidationException::withMessages(['voice_note' => 'The voice note is invalid. Please record it again.']);
            }
            $extension = ['mpeg' => 'mp3', 'x-wav' => 'wav'][$matches[1]] ?? $matches[1];
            $value = $matches[2];
        }
        $bytes = base64_decode($value, true);
        if ($bytes === false || $bytes === '' || strlen($bytes) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['voice_note' => 'The voice note is invalid or exceeds 10 MB. Please record it again.']);
        }
        return [$bytes, $extension];
    }

    public static function saveAudio(string $value): string
    {
        [$bytes, $extension] = self::audio($value);
        $directory = public_path('comment_attachment/audios');
        if (!is_dir($directory)) mkdir($directory, 0755, true);
        $filename = (string) \Illuminate\Support\Str::uuid().'.'.$extension;
        if (file_put_contents($directory.'/'.$filename, $bytes) === false) throw new \RuntimeException('Could not save the voice note.');
        return $filename;
    }
}
