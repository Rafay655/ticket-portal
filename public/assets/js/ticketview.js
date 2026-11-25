console.log("i am running")
const fileInput = document.getElementById('comment_attachment');
const filePreview = document.getElementById('filePreview');
const progressBar = document.getElementById('progressBar');
const removeFileBtn = document.getElementById('removeFileBtn');
let fileUrl = null;
var progress = 0;
function handleFileUpload() {
    const file = fileInput.files[0];
    if (file) {

        filePreview.style.display = 'flex';
        removeFileBtn.style.display = 'flex';
        fileUrl = URL.createObjectURL(file);
        // Simulate file upload progress
        simulateUploadProgress();
    }
}

function simulateUploadProgress() {

    const interval = setInterval(() => {
        progress += 10;
        progressBar.style.width = progress + '%';

        if (progress >= 100) {
            clearInterval(interval);
        }
    }, 300);
}
filePreview.onclick = function () {
    if (fileUrl && progress >= 100) {
        window.open(fileUrl, '_blank'); // Open the file in a new tab
    }
}

removeFileBtn.onclick = function () {
    fileInput.value = '';
    updateCommentSendState();
    progress = 0;
    filePreview.style.display = 'none';
    removeFileBtn.style.display = 'none';
    progressBar.style.width = '0%';
};



function toggleCommentForm() {
    const commentForm = document.getElementById('commentForm');
    if (commentForm.classList.contains('d-none')) {
        commentForm.classList.remove('d-none');
    } else {
        commentForm.classList.add('d-none');
    }
}


$(document).ready(function () {
    var $star_rating = $('.star-rating .fa');

    var SetRatingStar = function () {
        return $star_rating.each(function () {
            if (parseInt($('#rating').val()) >= parseInt($(this).data('rating'))) {
                return $(this).removeClass('fa-star-o').addClass('fa-star');
            } else {
                return $(this).removeClass('fa-star').addClass('fa-star-o');
            }
        });
    };

    $star_rating.on('click', function () {
        var rating = $(this).data('rating');
        $('#rating').val(rating);
        SetRatingStar();
    });

    SetRatingStar();
});

var review_open = false;
var other_open = true;  // Assuming "other-section" is initially open

function toggleSections() {
    const reviewSection = document.getElementById('rafay-section-1');
    const otherSection = document.getElementById('other-section');
    if (review_open) {
        reviewSection.style.display = 'none ';
        otherSection.style.display = 'block  ';
        review_open = false;
        other_open = true;
    } else {
        reviewSection.style.display = 'block ';
        otherSection.style.display = 'none ';
        review_open = true;
        other_open = false;
    }
}
let mediaRecorder;
let audioChunks = [];
let isRecording = false; // Declared globally
let timerInterval;
let seconds = 0;
let minutes = 0;
let stream; // Store the stream once microphone access is granted

const recordButton = document.getElementById("recordButton");
const recordIcon = document.getElementById("recordIcon");
const timerElement = document.getElementById("timer"); // Timer element
const audioPlayback = document.getElementById("audioPlayback");
const voiceNoteInput = document.getElementById("voiceNoteInput");
const voicepreview = document.getElementById("voicepreview");
const recordedTime = document.getElementById("recordedTime");
const removeVoiceBtn = document.getElementById("removeVoiceBtn");

voicepreview.onclick = function () {
    if (audioPlayback.paused) {
        try {
            audioPlayback.play(); // Play the audio
            voicepreview.classList.add("playing"); // Add the playing class
            removeVoiceBtn.style.display = "none";
        } catch (err) {
            console.error("Error playing the audio:", err);
        }
    } else {
        try {
            audioPlayback.pause(); // Pause the audio
            voicepreview.classList.remove("playing"); // Remove the playing class
            removeVoiceBtn.style.display = "flex";
        } catch (err) {
            console.error("Error pausing the audio:", err);
        }
    }
};

audioPlayback.onended = function () {
    try {
        voicepreview.classList.remove("playing"); // Remove the playing class to stop the animation
        removeVoiceBtn.style.display = "flex";
    } catch (err) {
        console.error("Error on audio ended:", err);
    }
};

// Function to request microphone access
async function getMicrophoneAccess() {
    try {
        if (!window.isSecureContext) {
            throw Object.assign(new Error('Insecure page'), {name: 'InsecureContextError'});
        }
        if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
            throw Object.assign(new Error('Unsupported recording'), {name: 'UnsupportedRecordingError'});
        }
        if (!stream || !stream.getAudioTracks().some(track => track.readyState === 'live')) {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        }
        return stream;
    } catch (err) {
        const messages = {
            InsecureContextError: ['Secure connection required', 'Open the portal using HTTPS to record a voice note. For local testing, use localhost or a local HTTPS address.'],
            UnsupportedRecordingError: ['Recording unavailable', 'This browser does not support voice recording. Open the portal in a supported browser such as Chrome, Edge, or Safari.'],
            NotAllowedError: ['Microphone permission blocked', 'Allow Microphone in the site permissions beside the address bar, then try recording again. If it is already allowed, check microphone access in your device privacy settings.'],
            SecurityError: ['Microphone access blocked', 'Your browser or device security settings are blocking microphone access. Allow microphone access for this site, then try again.'],
            NotFoundError: ['No microphone found', 'Connect or enable a microphone, then try recording again.'],
            NotReadableError: ['Microphone unavailable', 'Your microphone could not be opened. Check your device settings and close any other app using the microphone, then try again.'],
            AbortError: ['Recording interrupted', 'Microphone access was interrupted. Please try recording again.']
        };
        const [title, message] = messages[err.name] || ['Unable to start recording', 'The microphone could not be started. Check your browser microphone permissions and try again.'];
        await AppMessages.show({type: 'error', title, message});
        throw err;
    }
}

const commentSubmitForm = voiceNoteInput.form;
const commentSendButton = commentSubmitForm.querySelector('[type="submit"]');
const commentDescription = commentSubmitForm.querySelector('[name="description"]');
let isPreparingRecording = false;
let isProcessingAudio = false;
const recordingHint = document.createElement('span');
recordingHint.className = 'd-block text-sm text-muted mt-2';
recordingHint.setAttribute('role', 'status');
commentSendButton.insertAdjacentElement('afterend', recordingHint);

function hasCommentContent() {
    return Boolean(commentDescription.value.trim() || fileInput.files.length || voiceNoteInput.value.trim());
}
function updateCommentSendState() {
    const busy = isRecording || isPreparingRecording || isProcessingAudio;
    commentSendButton.disabled = busy || !hasCommentContent();
    recordingHint.textContent = isRecording ? 'Recording… Stop recording before sending.' :
        isProcessingAudio ? 'Preparing voice note…' : isPreparingRecording ? 'Waiting for microphone…' : '';
}
commentDescription.addEventListener('input', updateCommentSendState);
fileInput.addEventListener('change', updateCommentSendState);
commentSubmitForm.addEventListener('submit', event => {
    if (isRecording || isPreparingRecording || isProcessingAudio || !hasCommentContent()) {
        event.preventDefault();
        event.stopImmediatePropagation();
        AppMessages.show({type: 'warning', message: isRecording || isProcessingAudio || isPreparingRecording ?
            'Stop recording and wait for the voice note to finish processing before sending.' :
            'Enter a comment, attach a file, or record a voice note before sending.'});
    }
}, true);
updateCommentSendState();

function resetRecordingControls() {
    isRecording = false;
    isPreparingRecording = false;
    recordButton.classList.remove('active');
    recordIcon.classList.replace('fa-stop', 'fa-microphone');
    timerElement.style.display = 'none';
    stopTimer();
}
recordButton.onclick = async function () {
    if (isPreparingRecording || isProcessingAudio) return;
    if (isRecording) {
        isProcessingAudio = true;
        resetRecordingControls();
        updateCommentSendState();
        mediaRecorder.stop();
        return;
    }
    isPreparingRecording = true;
    updateCommentSendState();
    try {
        const micStream = await getMicrophoneAccess();
        mediaRecorder = new MediaRecorder(micStream);
        audioChunks = [];
        voiceNoteInput.value = '';
        updateCommentSendState();
        voicepreview.style.display = 'none';
        removeVoiceBtn.style.display = 'none';
        mediaRecorder.ondataavailable = event => { if (event.data.size) audioChunks.push(event.data); };
        mediaRecorder.onstop = async () => {
            isProcessingAudio = true;
            resetRecordingControls();
            updateCommentSendState();
            try {
                const blob = new Blob(audioChunks, {type: mediaRecorder.mimeType || 'audio/webm'});
                if (!blob.size) throw new Error('Empty recording');
                const data = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = reject;
                    reader.onabort = reject;
                    reader.readAsDataURL(blob);
                });
                if (audioPlayback.src.startsWith('blob:')) URL.revokeObjectURL(audioPlayback.src);
                audioPlayback.src = URL.createObjectURL(blob);
                voiceNoteInput.value = data;
                voicepreview.style.display = 'flex';
                removeVoiceBtn.style.display = 'flex';
            } catch (error) {
                voiceNoteInput.value = '';
                await AppMessages.show({type: 'error', message: 'The voice note could not be prepared. Please record it again.'});
            } finally {
                micStream.getTracks().forEach(track => track.stop());
                stream = null;
                isProcessingAudio = false;
                updateCommentSendState();
            }
        };
        mediaRecorder.start();
        isRecording = true;
        isPreparingRecording = false;
        recordButton.classList.add('active');
        recordIcon.classList.replace('fa-microphone', 'fa-stop');
        timerElement.style.display = 'block';
        seconds = 0; minutes = 0;
        updateTimer(); startTimer();
    } catch (error) {
        resetRecordingControls();
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
    } finally {
        updateCommentSendState();
    }
};

// Start the timer
function startTimer() {
    try {
        timerInterval = setInterval(() => {
            seconds++;
            if (seconds === 60) {
                seconds = 0;
                minutes++;
            }
            updateTimer();
        }, 1000);
    } catch (err) {
        console.error("Error starting the timer:", err);
    }
}

// Stop the timer
function stopTimer() {
    try {
        clearInterval(timerInterval);
    } catch (err) {
        console.error("Error stopping the timer:", err);
    }
}

// Update the timer display
function updateTimer() {
    try {
        const formattedMinutes = minutes < 10 ? `${minutes}` : minutes;
        const formattedSeconds = seconds < 10 ? `0${seconds}` : seconds;
        timerElement.textContent = `${formattedMinutes}:${formattedSeconds}`;
    } catch (err) {
        console.error("Error updating the timer:", err);
    }
}

// Remove voice note functionality
removeVoiceBtn.onclick = function () {
    try {
        audioPlayback.pause();
        audioPlayback.currentTime = 0; // Pause the audio
        audioChunks = [];
        console.log('Pausing the audio');
        voiceNoteInput.value = '';
        voicepreview.style.display = 'none';
        removeVoiceBtn.style.display = 'none';
        recordedTime.textContent = '00:00'; // Reset the displayed time
    } catch (err) {
        console.error("Error removing the voice note:", err);
    }
};

// Timer for audio playback
audioPlayback.onplay = function () {
    try {
        // Start updating the timer when the audio starts playing
        audioPlayback.ontimeupdate = function () {
            const currentTime = Math.floor(audioPlayback.currentTime);
            const minutes = Math.floor(currentTime / 60);
            const seconds = currentTime % 60;

            recordedTime.textContent = `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
        };
    } catch (err) {
        console.error("Error during audio playback:", err);
    }
};


function displayFileName1(){
    const label = document.getElementById('file-name-1');
    const file = document.getElementById('commentattachment');

    console.log();
    label.textContent = file.value.replace(/.*[\/\\]/, '')
}

