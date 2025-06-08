/**
 * BB Theme Custom Podcast Player Logic
 */
document.addEventListener('DOMContentLoaded', function() {
    const playerContainer = document.getElementById('bb-podcast-player-container');
    if (!playerContainer) return; // Oynatıcı yoksa çık

    const audioElement = document.getElementById('bb-podcast-audio-element');
    const playPauseButton = playerContainer.querySelector('.bb-play-pause-button');
    const playIcon = playPauseButton ? playPauseButton.querySelector('.play-icon') : null;
    const pauseIcon = playPauseButton ? playPauseButton.querySelector('.pause-icon') : null;
    const currentTimeSpan = playerContainer.querySelector('.current-time');
    const totalDurationSpan = playerContainer.querySelector('.total-duration');
    const currentTitleElement = document.getElementById('bb-podcast-current-title');
    const episodeList = document.querySelector('.podcast-episode-list');

    if (!audioElement || !playPauseButton || !playIcon || !pauseIcon || !currentTimeSpan || !totalDurationSpan || !currentTitleElement) {
        console.error('Podcast player elements not found.');
        return;
    }

    // --- Yardımcı Fonksiyonlar ---
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
    }

    function updatePlayPauseButton(isPlaying) {
        if (isPlaying) {
            playIcon.style.display = 'none';
            pauseIcon.style.display = 'inline-block';
        } else {
            playIcon.style.display = 'inline-block';
            pauseIcon.style.display = 'none';
        }
    }

    // --- Olay Dinleyicileri ---

    // Play/Pause Butonu
    playPauseButton.addEventListener('click', () => {
        if (audioElement.paused || audioElement.ended) {
            audioElement.play();
        } else {
            audioElement.pause();
        }
    });

    // Ses Oynatılmaya Başladığında
    audioElement.addEventListener('play', () => {
        updatePlayPauseButton(true);
    });

    // Ses Duraklatıldığında veya Bittiğinde
    audioElement.addEventListener('pause', () => {
        updatePlayPauseButton(false);
    });
    audioElement.addEventListener('ended', () => {
        updatePlayPauseButton(false);
        currentTimeSpan.textContent = formatTime(0); // Süreyi sıfırla
    });

    // Süre Bilgisi Yüklendiğinde
    audioElement.addEventListener('loadedmetadata', () => {
        totalDurationSpan.textContent = formatTime(audioElement.duration);
    });

    // Zaman İlerledikçe
    audioElement.addEventListener('timeupdate', () => {
        currentTimeSpan.textContent = formatTime(audioElement.currentTime);
        // İlerleme çubuğu eklenecekse burada güncellenir
    });

    // --- Liste Elemanlarına Tıklama ---
    if (episodeList) {
        episodeList.addEventListener('click', function(event) {
            const listItem = event.target.closest('.podcast-episode-item'); // Tıklanan li öğesini bul
            if (!listItem) return; // li değilse çık

            const audioSrc = listItem.dataset.audioSrc;
            const audioTitle = listItem.dataset.audioTitle;
            const audioDuration = listItem.dataset.audioDuration;

            if (audioSrc && audioTitle) {
                console.log('Loading episode:', audioTitle, audioSrc); // Debug
                currentTitleElement.textContent = audioTitle; // Başlığı güncelle
                audioElement.src = audioSrc; // Ses kaynağını değiştir
                totalDurationSpan.textContent = audioDuration ? audioDuration : '0:00'; // Süreyi güncelle
                currentTimeSpan.textContent = '0:00'; // Mevcut süreyi sıfırla
                audioElement.load(); // Yeni kaynağı yükle
                audioElement.play(); // Otomatik oynat
                updatePlayPauseButton(true); // Butonu güncelle

                // İsteğe bağlı: Aktif list item'ı vurgula
                const activeItem = episodeList.querySelector('.active');
                if (activeItem) {
                    activeItem.classList.remove('active');
                }
                listItem.classList.add('active');
            } else {
                console.warn('Missing audio data on list item:', listItem);
            }
        });

        // Klavye ile erişim için (Enter tuşu)
        episodeList.addEventListener('keydown', function(event) {
             if (event.key === 'Enter' || event.keyCode === 13) {
                 const listItem = event.target.closest('.podcast-episode-item');
                 if (listItem) {
                     listItem.click(); // Tıklama olayını tetikle
                 }
             }
        });
    }

    console.log('BB Podcast Player Initialized.'); // Debug
});