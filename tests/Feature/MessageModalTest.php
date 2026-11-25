<?php

namespace Tests\Feature;

use App\Support\FlashMessages;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class MessageModalTest extends TestCase
{
    public function test_success_error_and_failed_messages_are_available_to_modals(): void
    {
        $session = app('session.store');
        $session->put(['success' => 'Ticket saved.', 'error' => 'Access denied.', 'failed' => 'Employee email failed.']);
        $messages = FlashMessages::forSession($session);
        $this->assertCount(3, $messages);
        $this->assertSame(['error', 'error', 'success'], array_column($messages, 'type'));
        $this->assertContains('Ticket saved.', array_column($messages, 'message'));
    }

    public function test_authentication_status_is_a_human_readable_success_message(): void
    {
        $session = app('session.store');
        $session->put('status', 'verification-link-sent');
        $messages = FlashMessages::forSession($session);
        $this->assertSame('success', $messages[0]['type']);
        $this->assertStringContainsString('verification link', $messages[0]['message']);
    }

    public function test_identical_flash_messages_are_displayed_only_once(): void
    {
        $session = app('session.store');
        $session->put(['error' => 'Cannot create ticket.', 'failed' => 'Cannot create ticket.']);
        $this->assertCount(1, FlashMessages::forSession($session));
    }

    public function test_validation_errors_from_all_bags_are_grouped_in_one_modal(): void
    {
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['email' => ['Enter a valid email.']]));
        $errors->put('password', new MessageBag(['password' => ['Passwords do not match.']]));
        $messages = FlashMessages::forSession(app('session.store'), $errors);
        $this->assertCount(1, $messages);
        $this->assertSame('Please check your details', $messages[0]['title']);
        $this->assertSame("Enter a valid email.\nPasswords do not match.", $messages[0]['message']);
    }

    public function test_import_row_details_are_preserved_in_the_error_modal(): void
    {
        $failure = new class {
            public function row() { return 5; }
            public function errors() { return ['Email is missing.', 'Name is required.']; }
        };
        app('session.store')->put('failures', [$failure]);
        $messages = FlashMessages::forSession(app('session.store'));
        $this->assertSame('There were errors in your file', $messages[0]['title']);
        $this->assertSame('Row 4: Email is missing., Name is required.', $messages[0]['message']);
    }

    public function test_flash_text_is_encoded_safely_for_javascript(): void
    {
        app('session.store')->put('error', '</script><script>alert("unsafe")</script>');
        $html = view('layouts.partials.message-modals')->render();
        $this->assertStringContainsString('window.AppMessages.showMany', $html);
        $this->assertStringNotContainsString('<script>alert("unsafe")', $html);
        $this->assertStringContainsString('sweet_alerts.js', $html);
        $this->assertStringContainsString('message-modals.css', $html);
        $this->assertStringNotContainsString('Toastify', $html);
    }

    public function test_empty_session_does_not_open_a_message_modal(): void
    {
        $html = view('layouts.partials.message-modals')->render();
        $this->assertStringNotContainsString('window.AppMessages.showMany', $html);
    }

    public function test_login_page_includes_the_shared_modal_system(): void
    {
        $this->get('/login')->assertOk()->assertSee('sweet_alerts.js', false)->assertDontSee('toastify', false);
    }
}