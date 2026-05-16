window.FlashSite = window.FlashSite || {};

(function ($) {
    'use strict';

    function getHorizontalLabel(position) {
        if (position === 'center') {
            return 'Centro';
        }
        if (position === 'right') {
            return 'Direita';
        }
        return 'Esquerda';
    }

    function getVerticalLabel(position) {
        if (position === 'top') {
            return 'Topo';
        }
        if (position === 'bottom') {
            return 'Base';
        }
        return 'Centro';
    }

    function syncHeroSlideState() {
        const $heroPage = $('.fsc-hero-form');
        if (!$heroPage.length) {
            return;
        }

        let readyCount = 0;

        $('.fsc-hero-slide-card, .fsc-hero-accordion').each(function () {
            const $card = $(this);
            const $toggle = $card.find('.fsc-hero-enabled-toggle');
            const $label = $card.find('.fsc-hero-enabled-label').first();
            const $enabledBadge = $card.find('.fsc-hero-enabled-badge');
            const $readyBadge = $card.find('.fsc-hero-ready-badge');
            const $positionMeta = $card.find('.fsc-hero-position-meta');
            const $horizontalField = $card.find('select[name$="[content_horizontal_position]"]');
            const $verticalField = $card.find('select[name$="[content_vertical_position]"]');
            const desktopValue = parseInt($card.find('input[name$="[image_desktop_id]"]').val() || '0', 10);
            const enabled = $toggle.is(':checked');
            const ready = enabled && desktopValue > 0;
            const horizontal = String($horizontalField.val() || 'left');
            const vertical = String($verticalField.val() || 'center');

            if ($label.length) {
                $label.text(enabled ? 'Ativo' : 'Inativo');
                $card.attr('data-enabled-state', enabled ? '1' : '0');
            }

            if ($enabledBadge.length) {
                $enabledBadge.text(enabled ? 'Ativo' : 'Inativo');
                $enabledBadge.removeClass('is-enabled is-disabled').addClass(enabled ? 'is-enabled' : 'is-disabled');
            }

            if ($readyBadge.length) {
                $readyBadge.text(ready ? 'Pronto a mostrar' : 'Falta imagem principal');
                $readyBadge.removeClass('is-ready is-missing').addClass(ready ? 'is-ready' : 'is-missing');
            }

            if ($positionMeta.length) {
                $positionMeta.text('Conteúdo: ' + getHorizontalLabel(horizontal) + ' + ' + getVerticalLabel(vertical));
            }

            $card.toggleClass('is-ready', ready);
            if (ready) {
                readyCount += 1;
            }
        });

        $('.fsc-hero-active-count').text(String(readyCount));
    }

    function bindMediaField(button) {
        const target = button.data('target');
        const preview = button.data('preview');
        const frame = wp.media({
            title: 'Selecionar media',
            button: { text: 'Usar ficheiro' },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#' + target).val(attachment.id).trigger('change');

            if (preview) {
                const $preview = $('#' + preview);
                if ($preview.length) {
                    if (attachment.type === 'image' && attachment.url) {
                        $preview.html('<img src="' + attachment.url + '" alt="" /><div class="fsc-media-preview__meta">' + (attachment.filename || ('Attachment ID ' + attachment.id)) + '</div>');
                    } else {
                        $preview.text(attachment.filename || ('ID #' + attachment.id));
                    }
                }
            }

            syncHeroSlideState();
        });

        frame.open();
    }

    $(function () {
        $('.fsc-color-field').wpColorPicker();

        $(document).on('click', '.fsc-media-button', function (event) {
            event.preventDefault();
            bindMediaField($(this));
        });

        $(document).on('click', '.fsc-media-remove', function (event) {
            event.preventDefault();
            const target = $(this).data('target');
            const preview = $(this).data('preview');
            $('#' + target).val('0').trigger('change');
            if (preview) {
                $('#' + preview).html('<span class="description">Nenhuma imagem selecionada.</span>');
            }
            syncHeroSlideState();
        });

        $(document).on('change', '.fsc-hero-enabled-toggle, .fsc-hero-form input[name$="[image_desktop_id]"], .fsc-hero-form select[name$="[content_horizontal_position]"], .fsc-hero-form select[name$="[content_vertical_position]"]', function () {
            syncHeroSlideState();
        });

        $(document).on('click', '.fsc-copy-btn', function (event) {
            event.preventDefault();
            const button = $(this);
            const text = button.data('copyText') || '';
            if (!text) {
                return;
            }

            const applyCopiedState = function () {
                const original = button.text();
                button.addClass('is-copied').text('Copiado');
                window.setTimeout(function () {
                    button.removeClass('is-copied').text(original);
                }, 1200);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(applyCopiedState);
                return;
            }

            const temp = $('<textarea>').css({ position: 'absolute', left: '-9999px', top: '-9999px' }).val(text);
            $('body').append(temp);
            temp.trigger('select');
            document.execCommand('copy');
            temp.remove();
            applyCopiedState();
        });

        syncHeroSlideState();
    });
})(jQuery);
