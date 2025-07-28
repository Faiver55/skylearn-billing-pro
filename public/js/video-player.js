/**
 * Video Player JavaScript
 *
 * @package SkyLearnBillingPro
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * SkyLearn Video Player
     */
    window.SLBPVideoPlayer = {
        
        /**
         * Initialize video players
         */
        init: function() {
            this.bindEvents();
            this.initLazyLoading();
            this.initAccessibility();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Play button clicks
            $(document).on('click', '.slbp-video-play-button', this.handlePlayClick.bind(this));
            
            // Thumbnail clicks
            $(document).on('click', '.slbp-video-thumbnail', this.handleThumbnailClick.bind(this));
            
            // Playlist item clicks
            $(document).on('click', '.slbp-video-playlist-item', this.handlePlaylistClick.bind(this));
            
            // Chapter navigation
            $(document).on('click', '.slbp-video-chapter', this.handleChapterClick.bind(this));
            
            // Transcript toggle
            $(document).on('click', '.slbp-video-transcript-toggle', this.toggleTranscript.bind(this));
            
            // Video load events
            $(document).on('load', 'iframe[src*="youtube"], iframe[src*="vimeo"], iframe[src*="wistia"]', 
                          this.handleVideoLoad.bind(this));
        },

        /**
         * Initialize lazy loading for video thumbnails
         */
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var $thumbnail = $(entry.target);
                            this.loadVideoThumbnail($thumbnail);
                            observer.unobserve(entry.target);
                        }
                    }.bind(this));
                }.bind(this), {
                    rootMargin: '50px'
                });

                $('.slbp-video-thumbnail[data-lazy]').each(function() {
                    observer.observe(this);
                });
            } else {
                // Fallback for older browsers
                $('.slbp-video-thumbnail[data-lazy]').each(function() {
                    this.loadVideoThumbnail($(this));
                }.bind(this));
            }
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA labels and keyboard navigation
            $('.slbp-video-play-button').attr({
                'aria-label': slbpVideo.strings.play,
                'tabindex': '0'
            });
            
            $('.slbp-video-thumbnail').attr({
                'role': 'button',
                'tabindex': '0',
                'aria-label': slbpVideo.strings.play
            });
            
            // Keyboard navigation
            $(document).on('keydown', '.slbp-video-thumbnail, .slbp-video-play-button', function(e) {
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                    e.preventDefault();
                    $(this).click();
                }
            });
        },

        /**
         * Handle play button clicks
         */
        handlePlayClick: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $button = $(e.currentTarget);
            var $thumbnail = $button.closest('.slbp-video-thumbnail');
            
            this.playVideo($thumbnail);
        },

        /**
         * Handle thumbnail clicks
         */
        handleThumbnailClick: function(e) {
            e.preventDefault();
            
            var $thumbnail = $(e.currentTarget);
            this.playVideo($thumbnail);
        },

        /**
         * Handle playlist item clicks
         */
        handlePlaylistClick: function(e) {
            e.preventDefault();
            
            var $item = $(e.currentTarget);
            var $playlist = $item.closest('.slbp-video-playlist');
            var videoUrl = $item.data('video-url');
            var videoTitle = $item.find('.slbp-video-playlist-title').text();
            
            if (videoUrl) {
                // Update active state
                $playlist.find('.slbp-video-playlist-item').removeClass('active');
                $item.addClass('active');
                
                // Load video in main player
                this.loadVideoInPlayer($playlist.find('.slbp-video-playlist-main'), videoUrl, videoTitle);
            }
        },

        /**
         * Handle chapter clicks
         */
        handleChapterClick: function(e) {
            e.preventDefault();
            
            var $chapter = $(e.currentTarget);
            var time = $chapter.data('time');
            var $container = $chapter.closest('.slbp-video-container');
            var $iframe = $container.find('iframe');
            
            if (time && $iframe.length) {
                this.seekToTime($iframe, time);
            }
        },

        /**
         * Play video by replacing thumbnail with iframe
         */
        playVideo: function($thumbnail) {
            var videoUrl = $thumbnail.data('video-url');
            var $wrapper = $thumbnail.closest('.slbp-video-wrapper');
            var $container = $wrapper.closest('.slbp-video-container');
            
            if (!videoUrl) {
                console.error('No video URL found');
                return;
            }
            
            // Add autoplay parameter
            videoUrl = this.addAutoplayParam(videoUrl);
            
            // Show loading state
            this.showLoadingState($wrapper);
            
            // Create iframe
            var $iframe = $('<iframe>', {
                src: videoUrl,
                width: $wrapper.data('width') || 560,
                height: $wrapper.data('height') || 315,
                frameborder: 0,
                allowfullscreen: true,
                allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
                title: $container.find('.slbp-video-title').text() || 'Video Player'
            });
            
            // Set iframe styles for responsive design
            $iframe.css({
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%'
            });
            
            // Replace thumbnail with iframe
            $wrapper.html($iframe);
            
            // Track video play event
            this.trackVideoEvent('play', {
                url: videoUrl,
                title: $container.find('.slbp-video-title').text()
            });
        },

        /**
         * Load video in existing player
         */
        loadVideoInPlayer: function($player, videoUrl, title) {
            this.showLoadingState($player);
            
            var autoplayUrl = this.addAutoplayParam(videoUrl);
            
            var $iframe = $('<iframe>', {
                src: autoplayUrl,
                width: '100%',
                height: '100%',
                frameborder: 0,
                allowfullscreen: true,
                allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
                title: title || 'Video Player'
            });
            
            $player.html($iframe);
            
            this.trackVideoEvent('playlist_play', {
                url: videoUrl,
                title: title
            });
        },

        /**
         * Load video thumbnail
         */
        loadVideoThumbnail: function($thumbnail) {
            var thumbnailUrl = $thumbnail.data('lazy');
            var $img = $thumbnail.find('img');
            
            if (thumbnailUrl && $img.length) {
                $img.attr('src', thumbnailUrl);
                $thumbnail.removeAttr('data-lazy');
            }
        },

        /**
         * Show loading state
         */
        showLoadingState: function($container) {
            var $loading = $('<div class="slbp-video-loading">' +
                           '<span class="dashicons dashicons-update-alt"></span>' +
                           slbpVideo.strings.loading +
                           '</div>');
            
            $container.html($loading);
        },

        /**
         * Add autoplay parameter to video URL
         */
        addAutoplayParam: function(url) {
            if (url.indexOf('youtube.com') !== -1 || url.indexOf('youtu.be') !== -1) {
                return url + (url.indexOf('?') !== -1 ? '&' : '?') + 'autoplay=1&rel=0';
            } else if (url.indexOf('vimeo.com') !== -1) {
                return url + (url.indexOf('?') !== -1 ? '&' : '?') + 'autoplay=1';
            } else if (url.indexOf('wistia.com') !== -1) {
                return url + (url.indexOf('?') !== -1 ? '&' : '?') + 'autoPlay=true';
            }
            
            return url;
        },

        /**
         * Seek to specific time in video
         */
        seekToTime: function($iframe, timeInSeconds) {
            var src = $iframe.attr('src');
            
            if (src.indexOf('youtube.com') !== -1) {
                // YouTube: add start parameter
                var newSrc = src.replace(/[?&]start=\d+/, '');
                newSrc += (newSrc.indexOf('?') !== -1 ? '&' : '?') + 'start=' + timeInSeconds;
                $iframe.attr('src', newSrc);
            } else if (src.indexOf('vimeo.com') !== -1) {
                // Vimeo: add start parameter (in format #t=XmYs)
                var minutes = Math.floor(timeInSeconds / 60);
                var seconds = timeInSeconds % 60;
                var timeFragment = '#t=' + minutes + 'm' + seconds + 's';
                
                var newSrc = src.replace(/#t=\d+m\d+s/, '') + timeFragment;
                $iframe.attr('src', newSrc);
            }
            
            this.trackVideoEvent('seek', {
                time: timeInSeconds,
                url: src
            });
        },

        /**
         * Toggle video transcript
         */
        toggleTranscript: function(e) {
            e.preventDefault();
            
            var $toggle = $(e.currentTarget);
            var $content = $toggle.siblings('.slbp-video-transcript-content');
            var $icon = $toggle.find('.dashicons');
            
            if ($content.hasClass('hidden')) {
                $content.removeClass('hidden');
                $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
                $toggle.find('span:not(.dashicons)').text('Hide Transcript');
            } else {
                $content.addClass('hidden');
                $icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
                $toggle.find('span:not(.dashicons)').text('Show Transcript');
            }
        },

        /**
         * Handle video load events
         */
        handleVideoLoad: function(e) {
            var $iframe = $(e.target);
            var $container = $iframe.closest('.slbp-video-container');
            
            // Remove loading state
            $container.find('.slbp-video-loading').remove();
            
            // Track video load
            this.trackVideoEvent('load', {
                url: $iframe.attr('src'),
                title: $container.find('.slbp-video-title').text()
            });
        },

        /**
         * Track video events for analytics
         */
        trackVideoEvent: function(action, data) {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'video_' + action, {
                    'video_title': data.title || '',
                    'video_url': data.url || '',
                    'video_time': data.time || 0
                });
            }
            
            // WordPress custom event
            $(document).trigger('slbp_video_event', {
                action: action,
                data: data
            });
        },

        /**
         * Create video playlist
         */
        createPlaylist: function(videos, containerId, options) {
            var defaults = {
                autoplay: false,
                showThumbnails: true,
                showDuration: true,
                playerWidth: '100%',
                playerHeight: '400px'
            };
            
            options = $.extend(defaults, options);
            
            var $container = $('#' + containerId);
            if (!$container.length) {
                console.error('Playlist container not found: ' + containerId);
                return;
            }
            
            var playlistHtml = '<div class="slbp-video-playlist">';
            
            // Main player
            playlistHtml += '<div class="slbp-video-playlist-main" style="height: ' + options.playerHeight + ';">';
            if (videos.length > 0) {
                var firstVideo = videos[0];
                if (options.autoplay) {
                    playlistHtml += '<iframe src="' + this.addAutoplayParam(firstVideo.url) + '" ' +
                                   'width="100%" height="100%" frameborder="0" allowfullscreen></iframe>';
                } else {
                    playlistHtml += '<div class="slbp-video-thumbnail" data-video-url="' + firstVideo.url + '">';
                    playlistHtml += '<img src="' + firstVideo.thumbnail + '" alt="' + firstVideo.title + '">';
                    playlistHtml += '<button class="slbp-video-play-button"><span class="dashicons dashicons-controls-play"></span></button>';
                    playlistHtml += '</div>';
                }
            }
            playlistHtml += '</div>';
            
            // Sidebar with video list
            playlistHtml += '<div class="slbp-video-playlist-sidebar">';
            videos.forEach(function(video, index) {
                var activeClass = index === 0 ? ' active' : '';
                playlistHtml += '<div class="slbp-video-playlist-item' + activeClass + '" data-video-url="' + video.url + '">';
                
                if (options.showThumbnails && video.thumbnail) {
                    playlistHtml += '<div class="slbp-video-playlist-thumbnail">';
                    playlistHtml += '<img src="' + video.thumbnail + '" alt="' + video.title + '">';
                    playlistHtml += '</div>';
                }
                
                playlistHtml += '<div class="slbp-video-playlist-info">';
                playlistHtml += '<div class="slbp-video-playlist-title">' + video.title + '</div>';
                
                if (options.showDuration && video.duration) {
                    playlistHtml += '<div class="slbp-video-playlist-duration">' + video.duration + '</div>';
                }
                
                playlistHtml += '</div>';
                playlistHtml += '</div>';
            });
            playlistHtml += '</div>';
            
            playlistHtml += '</div>';
            
            $container.html(playlistHtml);
        },

        /**
         * Add video chapters
         */
        addVideoChapters: function(containerId, chapters) {
            var $container = $('#' + containerId);
            if (!$container.length || !chapters.length) {
                return;
            }
            
            var chaptersHtml = '<div class="slbp-video-chapters">';
            chapters.forEach(function(chapter) {
                chaptersHtml += '<div class="slbp-video-chapter" data-time="' + chapter.time + '">';
                chaptersHtml += '<div class="slbp-video-chapter-time">' + this.formatTime(chapter.time) + '</div>';
                chaptersHtml += '<h4 class="slbp-video-chapter-title">' + chapter.title + '</h4>';
                chaptersHtml += '</div>';
            }.bind(this));
            chaptersHtml += '</div>';
            
            $container.append(chaptersHtml);
        },

        /**
         * Format time in seconds to MM:SS format
         */
        formatTime: function(seconds) {
            var minutes = Math.floor(seconds / 60);
            var remainingSeconds = seconds % 60;
            return minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;
        },

        /**
         * Get video thumbnail from platform
         */
        getVideoThumbnail: function(url, callback) {
            var videoId = this.extractVideoId(url);
            var platform = this.detectPlatform(url);
            
            if (!videoId || !platform) {
                callback(null);
                return;
            }
            
            var thumbnailUrl;
            
            switch (platform) {
                case 'youtube':
                    thumbnailUrl = 'https://img.youtube.com/vi/' + videoId + '/maxresdefault.jpg';
                    break;
                case 'vimeo':
                    // Vimeo requires API call
                    $.getJSON('https://vimeo.com/api/v2/video/' + videoId + '.json')
                        .done(function(data) {
                            callback(data[0].thumbnail_large);
                        })
                        .fail(function() {
                            callback(null);
                        });
                    return;
                case 'wistia':
                    thumbnailUrl = 'https://embed-ssl.wistia.com/deliveries/' + videoId + '.jpg';
                    break;
                default:
                    callback(null);
                    return;
            }
            
            callback(thumbnailUrl);
        },

        /**
         * Extract video ID from URL
         */
        extractVideoId: function(url) {
            var patterns = {
                youtube: /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/,
                vimeo: /vimeo\.com\/([0-9]+)/,
                wistia: /wistia\.com\/medias\/([a-zA-Z0-9]+)/
            };
            
            for (var platform in patterns) {
                var match = url.match(patterns[platform]);
                if (match) {
                    return match[1];
                }
            }
            
            return null;
        },

        /**
         * Detect video platform from URL
         */
        detectPlatform: function(url) {
            if (url.indexOf('youtube.com') !== -1 || url.indexOf('youtu.be') !== -1) {
                return 'youtube';
            } else if (url.indexOf('vimeo.com') !== -1) {
                return 'vimeo';
            } else if (url.indexOf('wistia.com') !== -1) {
                return 'wistia';
            }
            
            return null;
        }
    };

    // Auto-initialize on document ready
    $(document).ready(function() {
        window.SLBPVideoPlayer.init();
    });

})(jQuery);