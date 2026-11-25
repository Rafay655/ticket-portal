const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/ticketview.js', 'utf8');
function setup() {
    const handlers = {};
    const button = {disabled:false, insertAdjacentElement(){}};
    const description = {value:'', addEventListener(){}};
    const form = {querySelector: selector => selector === '[type="submit"]' ? button : description, addEventListener: (name, fn) => handlers[name] = fn};
    const voice = {value:'', form};
    const file = {files:[], addEventListener(){}};
    const messages = [];
    const context = vm.createContext({voiceNoteInput:voice, fileInput:file, isRecording:false,
        document:{createElement:()=>({setAttribute(){}})}, AppMessages:{show: value=>messages.push(value)}});
    vm.runInContext(source.slice(source.indexOf('const commentSubmitForm ='), source.indexOf('function resetRecordingControls()')), context);
    return {context, button, description, voice, file, handlers, messages};
}
test('empty and whitespace comments stay disabled; text or audio enables Send', () => {
    const state=setup(); assert.equal(state.button.disabled,true);
    state.description.value='   '; vm.runInContext('updateCommentSendState()',state.context); assert.equal(state.button.disabled,true);
    state.description.value='Message'; vm.runInContext('updateCommentSendState()',state.context); assert.equal(state.button.disabled,false);
    state.description.value=''; state.voice.value='data:audio/webm;base64,AAAA'; vm.runInContext('updateCommentSendState()',state.context); assert.equal(state.button.disabled,false);
});
test('recording, microphone prompt and processing prevent Send even with text', () => {
    for(const busy of ['isRecording','isPreparingRecording','isProcessingAudio']) {
        const state=setup(); state.description.value='Message';
        vm.runInContext(busy+' = true; updateCommentSendState()',state.context); assert.equal(state.button.disabled,true,busy);
        let prevented=false; state.handlers.submit({preventDefault(){prevented=true},stopImmediatePropagation(){}});
        assert.equal(prevented,true); assert.match(state.messages[0].message,/Stop recording/);
        vm.runInContext(busy+' = false; updateCommentSendState()',state.context); assert.equal(state.button.disabled,false);
    }
});
test('empty submit is blocked and valid file-only submit is allowed', () => {
    const state=setup(); let prevented=false;
    const event={preventDefault(){prevented=true},stopImmediatePropagation(){}};
    state.handlers.submit(event); assert.equal(prevented,true);
    state.file.files=[{name:'file.pdf'}]; prevented=false; state.handlers.submit(event); assert.equal(prevented,false);
});
