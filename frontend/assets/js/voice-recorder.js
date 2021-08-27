(function( $ ){
    // Examples Websocket
    // https://medium.com/google-cloud/building-a-client-side-web-app-which-streams-audio-from-a-browser-microphone-to-a-server-part-ii-df20ddb47d4e
    // https://github.com/dialogflow/selfservicekiosk-audio-streaming/blob/master/examples/example2.html
    // WebRTC library
    // https://github.com/muaz-khan/RecordRTC/tree/master/dist
    $.fn.recordButton = function(options) {

        // global variables
        var isRecording = false;
        var isReady = false;
        var textResultEl = null;

        // create socket
        const socket = new WebSocket('wss://lk.cntr.one:27001');
        socket.addEventListener('open', function (event) {
            isReady = true;
        });
        socket.addEventListener('close', function (event) {
            isReady = false;
        });
        socket.addEventListener('error', function (event) {
            isReady = false;
        });
        socket.addEventListener('message', function (event) {
            let data = JSON.parse(event.data);

            if (textResultEl == null) {
                console.log(data);
                return;
            }
            console.log(typeof data);
            // partial text recognition
            if ('partial' in data) {
                console.log(data);
            }

            // complete text recognition
            if (('result' in data) && ('text' in data) && (data['text'] != null) && (data['text'].length)) {
                textResultEl.val(data['text']);
            }
        });

        // process instance
        return this.each(function(idx, element) {
            let textResultSelector = options['textResultSelector'];
            let audioStream = null;
            let btnElement = $(element);
            let recordAudio = null;

            function startRecording() {
                if (isRecording) return;

                isRecording = true;
                textResultEl = $(textResultSelector);

                // button decoration
                btnElement.removeClass('btn-info');
                btnElement.addClass('btn-danger');

                navigator.mediaDevices.getUserMedia({audio: true})
                    .then(function (stream) {
                        audioStream = stream;
                        recordAudio = RecordRTC(stream, {
                            type: 'audio',
                            mimeType: 'audio/webm',
                            sampleRate: 44100,
                            desiredSampRate: 8000,

                            recorderType: RecordRTC.StereoAudioRecorder,
                            numberOfAudioChannels: 1,
                            timeSlice: 1000,
                            ondataavailable: function (blob) {
                                socket.send(blob);
                            }
                        });

                        recordAudio.startRecording();
                    })
                    .catch(function (error) {
                        console.error(error);
                        // console.error(JSON.stringify(error));
                    });
            }

            function stopRecording() {
                isRecording = false;

                // button decoration
                btnElement.removeClass('btn-danger');
                btnElement.addClass('btn-info');

                recordAudio.stopRecording();
                if (audioStream != null) {
                    audioStream.getTracks().forEach(function(track) {
                        track.stop();
                    });
                }
            }

            $(this).on('click', function (el) {
                if (!isReady) {
                    $(el.target).html("Error: Can't connect to speech recognition server");
                    return;
                }
                if (isRecording) {
                    stopRecording();
                } else {
                    startRecording();
                }
            });
        });
    };
})( jQuery );

// $(document).ready(function () {
//     // https://github.com/dialogflow/selfservicekiosk-audio-streaming/blob/master/examples/example2.html
//     const startRecording = document.getElementById('start-recording');
//     const stopRecording = document.getElementById('stop-recording');
//
//     // Create WebSocket connection.
//     const socket = new WebSocket('wss://ra.welive.cloud:27001');
//     // Connection opened
//     socket.addEventListener('open', function (event) {
//         console.log("Opened!!!!");
//         //    socket.send('Hello Server!');
//     });
//
//     // Listen for messages
//     socket.addEventListener('message', function (event) {
//         console.log('Message from server ', event.data);
//     });
//
//     startRecording.onclick = function () {
//         startRecording.disabled = true;
//
//         navigator.mediaDevices.getUserMedia({audio: true})
//             .then(function (stream) {
//                 recordAudio = RecordRTC(stream, {
//                     type: 'audio',
//                     mimeType: 'audio/webm',
//                     sampleRate: 44100,
//                     desiredSampRate: 8000,
//
//                     recorderType: StereoAudioRecorder,
//                     numberOfAudioChannels: 1,
//
//
//                     //1)
//                     // get intervals based blobs
//                     // value in milliseconds
//                     // as you might not want to make detect calls every seconds
//                     timeSlice: 2000,
//
//                     //2)
//                     // as soon as the stream is available
//                     ondataavailable: function (blob) {
//                         console.log('ondataavailable', blob);
//                         socket.send(blob);
// //                    // 3
// //                    // making use of socket.io-stream for bi-directional
// //                    // streaming, create a stream
// //                    var stream = ss.createStream();
// //                    // stream directly to server
// //                    // it will be temp. stored locally
// //                    ss(socket).emit('stream', stream, {
// //                        name: 'stream.wav',
// //                        size: blob.size
// //                    });
// //                    // pipe the audio blob to the read stream
// //                    ss.createBlobReadStream(blob).pipe(stream);
//                     }
//                 });
//
//                 recordAudio.startRecording();
//                 stopRecording.disabled = false;
//             })
//             .catch(function (error) {
//                 console.error(JSON.stringify(error));
//             });
//     };
//
//     // 4)
//     // on stop button handler
//     stopRecording.onclick = function () {
//         // recording stopped
//         startRecording.disabled = false;
//         stopRecording.disabled = true;
//     };
//
// });
