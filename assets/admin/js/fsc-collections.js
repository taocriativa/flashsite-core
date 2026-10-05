/* FlashSite Core 2.6.0 · Ficha de itens das Coleções */
(function ($) {
    'use strict';

    function initTabs($item) {
        $item.on('click', '[data-fs-tab]', function () {
            var key = $(this).data('fs-tab');
            $item.find('[data-fs-tab]').removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');
            $item.find('[data-fs-panel]').each(function () {
                var active = $(this).data('fs-panel') === key;
                $(this).toggleClass('is-active', active).prop('hidden', !active);
            });
        });

        // Abre o primeiro separador com erro.
        var $errorTab = $item.find('.fsc-item__tab.has-error').first();
        if ($errorTab.length) {
            $errorTab.trigger('click');
        }
    }

    function syncGallery($gallery) {
        var ids = $gallery.find('[data-fs-gallery-list] li').map(function () {
            return String($(this).data('id'));
        }).get();
        $gallery.find('input[type=hidden]').val(ids.join(','));
        $gallery.find('.fsc-item__cover').remove();
        $gallery.find('[data-fs-gallery-list] li').first().append('<span class="fsc-item__cover">Capa</span>');
    }

    function thumbHtml(attachment) {
        var sizes = attachment.sizes || {};
        var url = (sizes.thumbnail || sizes.medium || sizes.full || {}).url || attachment.url;
        return $('<li class="fsc-item__thumb"></li>')
            .attr('data-id', attachment.id)
            .append($('<img alt="">').attr('src', url))
            .append('<button type="button" class="fsc-item__thumb-remove" aria-label="Remover foto">×</button>');
    }

    function initGallery($gallery) {
        var $list = $gallery.find('[data-fs-gallery-list]');
        if ($.fn.sortable) {
            $list.sortable({ items: 'li', tolerance: 'pointer', update: function () { syncGallery($gallery); } });
        }

        $gallery.on('click', '.fsc-item__thumb-remove', function () {
            $(this).closest('li').remove();
            syncGallery($gallery);
        });

        $gallery.on('click', '[data-fs-gallery-add]', function (event) {
            event.preventDefault();
            var frame = wp.media({
                title: 'Adicionar fotos',
                button: { text: 'Adicionar' },
                library: { type: 'image' },
                multiple: 'add'
            });
            frame.on('select', function () {
                var existing = $list.find('li').map(function () { return Number($(this).data('id')); }).get();
                frame.state().get('selection').each(function (model) {
                    var attachment = model.toJSON();
                    if (existing.indexOf(attachment.id) === -1) {
                        $list.append(thumbHtml(attachment));
                    }
                });
                syncGallery($gallery);
            });
            frame.open();
        });
    }

    function initMedia($media) {
        var isImage = $media.data('fs-media') === 'image';
        $media.on('click', '[data-fs-media-select]', function (event) {
            event.preventDefault();
            var frame = wp.media({
                title: isImage ? 'Escolher imagem' : 'Escolher ficheiro',
                button: { text: 'Usar' },
                library: isImage ? { type: 'image' } : {},
                multiple: false
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $media.find('input[type=hidden]').val(attachment.id);
                var $preview = $media.find('.fsc-item__media-preview').empty();
                if (isImage) {
                    var sizes = attachment.sizes || {};
                    $preview.append($('<img alt="">').attr('src', (sizes.thumbnail || sizes.full || {}).url || attachment.url));
                } else {
                    $preview.text(attachment.filename || attachment.title);
                }
                $media.find('[data-fs-media-clear]').prop('hidden', false);
            });
            frame.open();
        });
        $media.on('click', '[data-fs-media-clear]', function () {
            $media.find('input[type=hidden]').val('');
            $media.find('.fsc-item__media-preview').empty();
            $(this).prop('hidden', true);
        });
    }

    // "Preço sob consulta" desativa visualmente o campo de preço.
    function initPriceOnRequest($item) {
        var $flag = $('#fs_field_preco_sob_consulta');
        var $price = $('#fs_field_preco');
        if (!$flag.length || !$price.length) {
            return;
        }
        function sync() {
            $price.closest('.fsc-item__field').toggleClass('is-muted', $flag.is(':checked'));
        }
        $flag.on('change', sync);
        sync();
    }

    $(function () {
        $('[data-fs-item]').each(function () {
            initTabs($(this));
            initPriceOnRequest($(this));
        });
        $('[data-fs-gallery]').each(function () { initGallery($(this)); });
        $('[data-fs-media]').each(function () { initMedia($(this)); });
    });
})(jQuery);
