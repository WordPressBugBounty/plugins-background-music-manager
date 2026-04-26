document.addEventListener('DOMContentLoaded', function() {
    var audio = document.getElementById('bmmw-audio');
    if (audio) {
        audio.volume = bmmw_settings.volume;
        if (bmmw_settings.loop) { 
            audio.loop = true;
        }

        var playAudio = function() {
            audio.play().catch(function(error) {
                console.log('Autoplay failed:', error);
            });
            document.removeEventListener('click', playAudio);
            document.removeEventListener('keydown', playAudio);
        };

        audio.play().catch(function(error) {
            console.log('Autoplay blocked:', error);
            document.addEventListener('click', playAudio);
            document.addEventListener('keydown', playAudio);
        });

        if (bmmw_settings.play_time > 0) { 
            setTimeout(function() {
                audio.pause();
            }, bmmw_settings.play_time * 1000);
        }
    }
});