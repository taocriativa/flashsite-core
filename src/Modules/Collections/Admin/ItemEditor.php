<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Admin;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\Currency;
use FlashSite\Core\Domain\Collections\FieldDefinition;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\PriceBandResolver;
use FlashSite\Core\Domain\Collections\TaxonomyDefinition;
use FlashSite\Core\Modules\Collections\ItemPersistence;

/**
 * Ficha do item no painel: metabox principal com separadores por grupo e coluna lateral
 * com taxonomias e campos "side". Usa o editor clássico.
 *
 * Validação: os valores são sempre gravados; se houver erros, o item volta a rascunho
 * e os erros aparecem no topo da ficha.
 *
 * @since 2.6.0
 */
final class ItemEditor
{
    private const NONCE_ACTION = 'flashsite_collection_item_save';
    private const NONCE_NAME = '_fs_item_nonce';
    private const ERRORS_TRANSIENT = 'fs_item_errors_%d_%d';

    /** @var array<string, CollectionPresetInterface> post_type => preset */
    private array $presets = [];

    private bool $saving = false;

    public function __construct(private ItemPersistence $persistence, private ItemReader $reader, private ?CollectionSettings $settings = null) {}

    /** @param array<string, CollectionPresetInterface> $presets */
    public function register(array $presets): void
    {
        foreach ($presets as $preset) {
            $this->presets[$preset->postType()] = $preset;
            add_action('add_meta_boxes_' . $preset->postType(), [$this, 'addMetaBoxes']);
            add_action('save_post_' . $preset->postType(), [$this, 'save'], 10, 2);
            add_action('rest_after_insert_' . $preset->postType(), [$this, 'afterRestInsert']);
        }
        if ($this->presets === []) {
            return;
        }
        add_filter('wp_insert_post_data', [$this, 'preventInvalidPublish'], 10, 2);
        add_filter('enter_title_here', [$this, 'titlePlaceholder'], 10, 2);
        add_action('edit_form_after_title', [$this, 'editorHeading']);
        add_action('admin_notices', [$this, 'renderErrors']);
        add_filter('redirect_post_location', [$this, 'redirectLocation'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function presetFor(string $postType): ?CollectionPresetInterface
    {
        return $this->presets[$postType] ?? null;
    }

    public function addMetaBoxes(\WP_Post $post): void
    {
        $preset = $this->presetFor($post->post_type);
        if ($preset === null) {
            return;
        }
        // A ficha substitui as caixas nativas que expõem meta em bruto ou duplicam a capa.
        remove_meta_box('postcustom', $post->post_type, 'normal');
        if ($preset->setting('cover_from')) {
            remove_meta_box('postimagediv', $post->post_type, 'side');
        }

        add_meta_box('fs_item_fields', 'Ficha · ' . $preset->labels()['singular'], fn (\WP_Post $p) => $this->renderMain($preset, $p), $post->post_type, 'normal', 'high');
        add_meta_box('fs_item_side', 'Classificação', fn (\WP_Post $p) => $this->renderSide($preset, $p), $post->post_type, 'side', 'high');
    }

    public function renderMain(CollectionPresetInterface $preset, \WP_Post $post): void
    {
        $values = $this->reader->read($preset, $post->ID, true);
        $errors = $this->peekErrors($post->ID);
        $groups = [];
        foreach ($preset->fields() as $field) {
            if ($field->placement === 'main') {
                $groups[$field->group][] = $field;
            }
        }

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        echo '<div class="fsc-item" data-fs-item>';
        echo '<nav class="fsc-item__tabs" role="tablist">';
        $first = true;
        foreach ($preset->groups() as $groupKey => $groupLabel) {
            if (empty($groups[$groupKey])) {
                continue;
            }
            $hasError = array_intersect(array_map(static fn (FieldDefinition $f) => $f->key, $groups[$groupKey]), array_keys($errors)) !== [];
            $private = $this->isPrivateGroup($groups[$groupKey]);
            printf(
                '<button type="button" class="fsc-item__tab%s%s" role="tab" aria-selected="%s" data-fs-tab="%s">%s%s</button>',
                $first ? ' is-active' : '',
                $hasError ? ' has-error' : '',
                $first ? 'true' : 'false',
                esc_attr((string) $groupKey),
                esc_html($groupLabel),
                $private ? ' <span class="dashicons dashicons-lock" aria-label="Privado"></span>' : ''
            );
            $first = false;
        }
        echo '</nav>';

        $first = true;
        foreach ($preset->groups() as $groupKey => $groupLabel) {
            if (empty($groups[$groupKey])) {
                continue;
            }
            printf('<section class="fsc-item__panel%s" data-fs-panel="%s"%s>', $first ? ' is-active' : '', esc_attr((string) $groupKey), $first ? '' : ' hidden');
            if ($this->isPrivateGroup($groups[$groupKey])) {
                echo '<p class="fsc-item__private-note"><span class="dashicons dashicons-lock"></span> Informação privada. Nunca aparece no site.</p>';
            }
            echo '<div class="fsc-item__grid">';
            foreach ($groups[$groupKey] as $field) {
                $this->renderField($field, $values[$field->key] ?? $field->type->emptyValue(), $errors[$field->key] ?? '');
            }
            echo '</div></section>';
            $first = false;
        }
        echo '</div>';
    }

    public function renderSide(CollectionPresetInterface $preset, \WP_Post $post): void
    {
        $values = $this->reader->read($preset, $post->ID, true);
        $errors = $this->peekErrors($post->ID);
        echo '<div class="fsc-item-side">';

        foreach ($preset->fields() as $field) {
            if ($field->placement === 'side') {
                $this->renderField($field, $values[$field->key] ?? $field->type->emptyValue(), $errors[$field->key] ?? '');
            }
        }

        foreach ($preset->taxonomies() as $taxonomy) {
            $this->renderTaxonomy($preset, $taxonomy, $post, $errors['tax_' . $taxonomy->key] ?? '');
        }
        echo '</div>';
    }

    private function renderTaxonomy(CollectionPresetInterface $preset, TaxonomyDefinition $taxonomy, \WP_Post $post, string $error): void
    {
        $name = $taxonomy->taxonomyName($preset->postType());
        $assigned = wp_get_object_terms($post->ID, $name, ['fields' => 'ids']);
        $assigned = is_array($assigned) ? array_map('intval', $assigned) : [];

        if ($taxonomy->auto) {
            $terms = wp_get_object_terms($post->ID, $name, ['fields' => 'names']);
            $label = is_array($terms) && $terms !== [] ? (string) $terms[0] : 'Calculada ao guardar';
            printf(
                '<div class="fsc-item-side__block"><span class="fsc-item__label">%s</span><p class="fsc-item__auto">%s</p><p class="fsc-item__help">Automática. Usada nos filtros do site.</p></div>',
                esc_html($taxonomy->singularLabel),
                esc_html($label)
            );
            return;
        }

        $terms = get_terms(['taxonomy' => $name, 'hide_empty' => false, 'orderby' => $taxonomy->terms !== [] ? 'term_id' : 'name']);
        $terms = is_array($terms) ? $terms : [];
        if ($taxonomy->terms !== []) {
            // Termos fixos pela ordem do preset.
            $order = array_flip(array_keys($taxonomy->terms));
            usort($terms, static fn ($a, $b) => ($order[$a->slug] ?? 99) <=> ($order[$b->slug] ?? 99));
        }
        if ($assigned === [] && $taxonomy->default !== '' && $post->post_status === 'auto-draft') {
            foreach ($terms as $term) {
                if ($term->slug === $taxonomy->default) {
                    $assigned = [(int) $term->term_id];
                }
            }
        }

        printf('<fieldset class="fsc-item-side__block%s"><legend class="fsc-item__label">%s%s</legend>', $error !== '' ? ' has-error' : '', esc_html($taxonomy->singularLabel), $taxonomy->required ? ' <span class="fsc-item__req">*</span>' : '');
        $inputName = sprintf('fs_tax[%s][]', $taxonomy->key);
        $type = $taxonomy->single ? 'radio' : 'checkbox';

        if ($terms === []) {
            echo '<p class="fsc-item__help">Ainda sem opções.</p>';
        }
        echo '<ul class="fsc-item__terms">';
        $this->renderTermTree($terms, 0, $inputName, $type, $assigned, $taxonomy->hierarchical);
        echo '</ul>';
        if ($taxonomy->single && ! $taxonomy->required) {
            printf('<label class="fsc-item__term fsc-item__term--none"><input type="radio" name="%s" value=""%s> Nenhum</label>', esc_attr($inputName), $assigned === [] ? ' checked' : '');
        }

        if (! $taxonomy->locked && current_user_can('edit_' . $preset->capabilityPlural())) {
            printf('<details class="fsc-item__newterm"><summary>Adicionar %s</summary>', esc_html(mb_strtolower($taxonomy->singularLabel)));
            printf('<input type="text" class="widefat" name="fs_tax_new[%s][name]" placeholder="Nome">', esc_attr($taxonomy->key));
            if ($taxonomy->hierarchical && $terms !== []) {
                printf('<select class="widefat" name="fs_tax_new[%s][parent]"><option value="0">Sem pai (ex.: concelho)</option>', esc_attr($taxonomy->key));
                foreach ($terms as $term) {
                    if ((int) $term->parent === 0) {
                        printf('<option value="%d">Dentro de %s</option>', (int) $term->term_id, esc_html($term->name));
                    }
                }
                echo '</select>';
            }
            echo '<p class="fsc-item__help">É criada ao guardar.</p></details>';
        }
        if ($taxonomy->help !== '') {
            printf('<p class="fsc-item__help">%s</p>', esc_html($taxonomy->help));
        }
        if ($error !== '') {
            printf('<p class="fsc-item__error">%s</p>', esc_html($error));
        }
        echo '</fieldset>';
    }

    /** @param list<object> $terms */
    private function renderTermTree(array $terms, int $parent, string $inputName, string $type, array $assigned, bool $hierarchical): void
    {
        foreach ($terms as $term) {
            if ($hierarchical && (int) $term->parent !== $parent) {
                continue;
            }
            printf(
                '<li><label class="fsc-item__term"><input type="%s" name="%s" value="%d"%s> %s</label>',
                esc_attr($type),
                esc_attr($inputName),
                (int) $term->term_id,
                in_array((int) $term->term_id, $assigned, true) ? ' checked' : '',
                esc_html($term->name)
            );
            if ($hierarchical) {
                $hasChildren = array_filter($terms, static fn ($t) => (int) $t->parent === (int) $term->term_id) !== [];
                if ($hasChildren) {
                    echo '<ul class="fsc-item__terms fsc-item__terms--child">';
                    $this->renderTermTree($terms, (int) $term->term_id, $inputName, $type, $assigned, true);
                    echo '</ul>';
                }
            }
            echo '</li>';
        }
    }

    private function renderField(FieldDefinition $field, mixed $value, string $error): void
    {
        $id = 'fs_field_' . $field->key;
        $name = 'fs_fields[' . $field->key . ']';
        $wide = in_array($field->type, [FieldType::Textarea, FieldType::Richtext, FieldType::Gallery, FieldType::ListOfText, FieldType::Multiselect], true);

        printf(
            '<div class="fsc-item__field fsc-item__field--%s%s%s" data-fs-field="%s">',
            esc_attr($field->type->value),
            $wide ? ' is-wide' : '',
            $error !== '' ? ' has-error' : '',
            esc_attr($field->key)
        );

        if ($field->type !== FieldType::Bool) {
            printf(
                '<label class="fsc-item__label" for="%s">%s%s</label>',
                esc_attr($id),
                esc_html($field->label),
                $field->required ? ' <span class="fsc-item__req">*</span>' : ''
            );
        }

        $placeholder = $field->placeholder !== '' ? sprintf(' placeholder="%s"', esc_attr($field->placeholder)) : '';
        $required = $field->required ? ' aria-required="true"' : '';

        switch ($field->type) {
            case FieldType::Textarea:
                printf('<textarea id="%s" name="%s" rows="4" class="widefat"%s%s>%s</textarea>', esc_attr($id), esc_attr($name), $placeholder, $required, esc_textarea((string) $value));
                break;
            case FieldType::Richtext:
                wp_editor((string) $value, $id, ['textarea_name' => $name, 'media_buttons' => false, 'teeny' => true, 'textarea_rows' => 6]);
                break;
            case FieldType::Number:
            case FieldType::Money:
                $currency = $this->settings?->currency() ?? Currency::of(Currency::DEFAULT);
                $display = $value === null ? '' : ($field->type === FieldType::Money ? $currency->number((float) $value) : rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ','));
                $unit = $field->type === FieldType::Money ? $currency->symbol() : $field->unit;
                printf(
                    '<div class="fsc-item__affix"><input type="text" inputmode="decimal" id="%s" name="%s" value="%s" class="widefat"%s%s>%s</div>',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($display),
                    $placeholder,
                    $required,
                    $unit !== '' ? '<span class="fsc-item__unit">' . esc_html($unit) . '</span>' : ''
                );
                break;
            case FieldType::Bool:
                printf(
                    '<label class="fsc-item__check"><input type="hidden" name="%2$s" value="0"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>',
                    esc_attr($id),
                    esc_attr($name),
                    $value === true ? ' checked' : '',
                    esc_html($field->label)
                );
                break;
            case FieldType::Select:
                printf('<select id="%s" name="%s" class="widefat"%s><option value="">Escolher…</option>', esc_attr($id), esc_attr($name), $required);
                foreach ($field->options as $optionValue => $optionLabel) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($optionValue), (string) $value === $optionValue ? ' selected' : '', esc_html($optionLabel));
                }
                echo '</select>';
                break;
            case FieldType::Multiselect:
                echo '<div class="fsc-item__chips">';
                printf('<input type="hidden" name="%s[]" value="">', esc_attr($name));
                foreach ($field->options as $optionValue => $optionLabel) {
                    printf(
                        '<label class="fsc-item__chip"><input type="checkbox" name="%s[]" value="%s"%s> %s</label>',
                        esc_attr($name),
                        esc_attr($optionValue),
                        in_array($optionValue, (array) $value, true) ? ' checked' : '',
                        esc_html($optionLabel)
                    );
                }
                echo '</div>';
                break;
            case FieldType::Date:
                printf('<input type="date" id="%s" name="%s" value="%s" class="widefat"%s>', esc_attr($id), esc_attr($name), esc_attr((string) $value), $required);
                break;
            case FieldType::Time:
                printf('<input type="time" id="%s" name="%s" value="%s" class="widefat"%s>', esc_attr($id), esc_attr($name), esc_attr((string) $value), $required);
                break;
            case FieldType::Url:
                printf('<input type="url" id="%s" name="%s" value="%s" class="widefat"%s%s>', esc_attr($id), esc_attr($name), esc_attr((string) $value), $placeholder, $required);
                break;
            case FieldType::Email:
                printf('<input type="email" id="%s" name="%s" value="%s" class="widefat"%s%s>', esc_attr($id), esc_attr($name), esc_attr((string) $value), $placeholder, $required);
                break;
            case FieldType::Image:
            case FieldType::File:
                $this->renderMediaSingle($field, $id, $name, (int) $value);
                break;
            case FieldType::Gallery:
                $this->renderGallery($id, $name, (array) $value);
                break;
            case FieldType::Geo:
                $lat = isset($value['lat']) ? (string) $value['lat'] : '';
                $lng = isset($value['lng']) ? (string) $value['lng'] : '';
                printf(
                    '<div class="fsc-item__geo"><input type="text" inputmode="decimal" id="%1$s" name="%2$s[lat]" value="%3$s" placeholder="Latitude (ex.: 38.7223)"><input type="text" inputmode="decimal" name="%2$s[lng]" value="%4$s" placeholder="Longitude (ex.: -9.1393)"></div>',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($lat),
                    esc_attr($lng)
                );
                break;
            case FieldType::ListOfText:
                printf('<textarea id="%s" name="%s" rows="4" class="widefat" placeholder="Um item por linha">%s</textarea>', esc_attr($id), esc_attr($name), esc_textarea(implode("\n", (array) $value)));
                break;
            default:
                printf('<input type="text" id="%s" name="%s" value="%s" class="widefat"%s%s%s>', esc_attr($id), esc_attr($name), esc_attr((string) $value), $placeholder, $required, $field->maxLength !== null ? ' maxlength="' . (int) $field->maxLength . '"' : '');
        }

        if ($field->help !== '') {
            printf('<p class="fsc-item__help">%s</p>', esc_html($field->help));
        }
        if ($error !== '') {
            printf('<p class="fsc-item__error" role="alert">%s</p>', esc_html($error));
        }
        echo '</div>';
    }

    private function renderMediaSingle(FieldDefinition $field, string $id, string $name, int $attachmentId): void
    {
        $isImage = $field->type === FieldType::Image;
        $preview = '';
        if ($attachmentId > 0) {
            $preview = $isImage
                ? (string) wp_get_attachment_image($attachmentId, 'thumbnail')
                : esc_html(basename((string) get_attached_file($attachmentId)));
        }
        printf(
            '<div class="fsc-item__media" data-fs-media="%s"><input type="hidden" id="%s" name="%s" value="%s"><div class="fsc-item__media-preview">%s</div><button type="button" class="button" data-fs-media-select>%s</button> <button type="button" class="button-link fsc-item__remove" data-fs-media-clear%s>Remover</button></div>',
            $isImage ? 'image' : 'file',
            esc_attr($id),
            esc_attr($name),
            $attachmentId > 0 ? (string) $attachmentId : '',
            $preview,
            $isImage ? 'Escolher imagem' : 'Escolher ficheiro',
            $attachmentId > 0 ? '' : ' hidden'
        );
    }

    /** @param array<int, mixed> $ids */
    private function renderGallery(string $id, string $name, array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        printf('<div class="fsc-item__gallery" data-fs-gallery><input type="hidden" id="%s" name="%s" value="%s">', esc_attr($id), esc_attr($name), esc_attr(implode(',', $ids)));
        echo '<ul class="fsc-item__gallery-list" data-fs-gallery-list>';
        foreach ($ids as $index => $attachmentId) {
            $thumb = (string) wp_get_attachment_image($attachmentId, 'thumbnail');
            if ($thumb === '') {
                continue;
            }
            printf(
                '<li class="fsc-item__thumb" data-id="%d">%s%s<button type="button" class="fsc-item__thumb-remove" aria-label="Remover foto">×</button></li>',
                $attachmentId,
                $thumb,
                $index === 0 ? '<span class="fsc-item__cover">Capa</span>' : ''
            );
        }
        echo '</ul><button type="button" class="button button-secondary" data-fs-gallery-add><span class="dashicons dashicons-format-gallery"></span> Adicionar fotos</button></div>';
    }

    /**
     * @param \WP_Post $post
     */
    public function save(int $postId, $post): void
    {
        if ($this->saving || ! $post instanceof \WP_Post) {
            return;
        }
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }
        $preset = $this->presetFor($post->post_type);
        if ($preset === null) {
            return;
        }
        if (! isset($_POST[self::NONCE_NAME]) || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        $this->saving = true;

        $fields = isset($_POST['fs_fields']) && is_array($_POST['fs_fields']) ? wp_unslash($_POST['fs_fields']) : [];
        $tax = isset($_POST['fs_tax']) && is_array($_POST['fs_tax']) ? wp_unslash($_POST['fs_tax']) : [];
        $newTerms = isset($_POST['fs_tax_new']) && is_array($_POST['fs_tax_new']) ? wp_unslash($_POST['fs_tax_new']) : [];

        $errors = $this->persistence->saveFields($preset, $postId, $fields);
        $errors += $this->persistence->saveTerms($preset, $postId, $tax, $newTerms);
        $this->persistence->syncCover($preset, $postId);

        if ($errors !== []) {
            set_transient(sprintf(self::ERRORS_TRANSIENT, get_current_user_id(), $postId), $errors, 5 * MINUTE_IN_SECONDS);
            // Salvaguarda: preventInvalidPublish já evita a publicação; isto cobre casos-limite.
            if (in_array($post->post_status, ['publish', 'future'], true)) {
                wp_update_post(['ID' => $postId, 'post_status' => 'draft']);
            }
        } else {
            delete_transient(sprintf(self::ERRORS_TRANSIENT, get_current_user_id(), $postId));
        }

        $this->saving = false;
    }

    /**
     * Antes de gravar: se a ficha tiver erros, o item não chega a ser publicado (fica rascunho).
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $postarr
     * @return array<string, mixed>
     */
    public function preventInvalidPublish(array $data, array $postarr): array
    {
        $preset = $this->presetFor((string) ($data['post_type'] ?? ''));
        if ($preset === null || ! in_array($data['post_status'] ?? '', ['publish', 'future'], true)) {
            return $data;
        }
        if (! isset($_POST[self::NONCE_NAME]) || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return $data;
        }
        $fields = isset($_POST['fs_fields']) && is_array($_POST['fs_fields']) ? wp_unslash($_POST['fs_fields']) : [];
        $tax = isset($_POST['fs_tax']) && is_array($_POST['fs_tax']) ? wp_unslash($_POST['fs_tax']) : [];
        $errors = $this->persistence->validateInput($preset, (int) ($postarr['ID'] ?? 0), $fields, $tax);
        if ($errors !== []) {
            $data['post_status'] = 'draft';
        }
        return $data;
    }

    /** Gravações pela REST (editor de blocos não é usado, mas a API sim) também recalculam faixa e capa. */
    public function afterRestInsert(\WP_Post $post): void
    {
        $preset = $this->presetFor($post->post_type);
        if ($preset === null) {
            return;
        }
        $this->persistence->syncPriceBand($preset, $post->ID);
        $this->persistence->syncCover($preset, $post->ID);
    }

    public function redirectLocation(string $location, int $postId): string
    {
        $post = get_post($postId);
        if ($post === null || $this->presetFor($post->post_type) === null) {
            return $location;
        }
        if (get_transient(sprintf(self::ERRORS_TRANSIENT, get_current_user_id(), $postId)) !== false) {
            // Evita a mensagem "publicado" quando o item voltou a rascunho.
            return add_query_arg('message', 10, remove_query_arg('message', $location));
        }
        return $location;
    }

    public function renderErrors(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->base !== 'post' || $this->presetFor((string) $screen->post_type) === null) {
            return;
        }
        $postId = isset($_GET['post']) ? absint($_GET['post']) : 0;
        if ($postId === 0) {
            return;
        }
        $errors = $this->peekErrors($postId);
        if ($errors === []) {
            return;
        }
        echo '<div class="notice notice-error fsc-item-notice"><p><strong>Faltam alguns dados. O registo ficou guardado como rascunho e ainda não está no site.</strong></p><ul>';
        foreach ($errors as $message) {
            printf('<li>%s</li>', esc_html($message));
        }
        echo '</ul></div>';
    }

    public function titlePlaceholder(string $text, \WP_Post $post): string
    {
        $preset = $this->presetFor($post->post_type);
        return $preset !== null ? (string) ($preset->labels()['title_placeholder'] ?? 'Título') : $text;
    }

    public function editorHeading(\WP_Post $post): void
    {
        $preset = $this->presetFor($post->post_type);
        if ($preset === null || ! in_array('editor', $preset->supports(), true)) {
            return;
        }
        printf('<h2 class="fsc-item__editor-heading">%s</h2>', esc_html((string) ($preset->labels()['editor_label'] ?? 'Descrição')));
    }

    public function enqueueAssets(string $hook): void
    {
        if (! in_array($hook, ['post.php', 'post-new.php', 'edit.php'], true)) {
            return;
        }
        $screen = get_current_screen();
        if ($screen === null || $this->presetFor((string) $screen->post_type) === null) {
            return;
        }
        wp_enqueue_style('flashsite-core-collections', FLASHSITE_CORE_URL . 'assets/admin/css/fsc-collections.css', [], FLASHSITE_CORE_VERSION);
        if ($hook === 'edit.php') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('flashsite-core-collections', FLASHSITE_CORE_URL . 'assets/admin/js/fsc-collections.js', ['jquery', 'jquery-ui-sortable'], FLASHSITE_CORE_VERSION, true);
    }

    /** @return array<string, string> */
    private function peekErrors(int $postId): array
    {
        $errors = get_transient(sprintf(self::ERRORS_TRANSIENT, get_current_user_id(), $postId));
        return is_array($errors) ? $errors : [];
    }

    /** @param list<FieldDefinition> $fields */
    private function isPrivateGroup(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field->public) {
                return false;
            }
        }
        return true;
    }
}
