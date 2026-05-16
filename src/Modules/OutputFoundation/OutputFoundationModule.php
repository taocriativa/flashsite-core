<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\OutputFoundation;

use FlashSite\Core\Core\Contracts\LoggerInterface;
use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Modules\OutputFoundation\Elementor\AccentColorTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\AddressTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\AdminEmailTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\BusinessNameTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\BusinessTypeTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\CityRegionTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\CountryTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\DoctoraliaUrlTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\EmailLinkTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\EmailTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\FacebookUrlTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\FullAddressTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\GoogleMapsUrlTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\HoursFormattedTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\InstagramUrlTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\LinkedinUrlTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\LogoDarkTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\LogoLightTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\PhoneLinkTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\PhoneTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\PostalCodeTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\PrimaryColorTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\SecondaryColorTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\TaglineTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\TaxIdTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\TiktokUrlTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\WebsiteTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\WhatsAppLinkTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\WhatsAppTag;
use FlashSite\Core\Modules\OutputFoundation\Elementor\YoutubeUrlTag;
use FlashSite\Core\Domain\Business\BusinessData;

final class OutputFoundationModule implements ModuleInterface
{
    /** @var array<string, bool> */
    private const BLOCKED_PUBLIC_OUTPUT_PATHS = [
        'contact.email_admin' => true,
        'business.admin_email' => true,
    ];

    /** @var list<class-string> */
    private const ELEMENTOR_TAG_CLASSES = [
        BusinessNameTag::class,
        BusinessTypeTag::class,
        TaglineTag::class,
        TaxIdTag::class,
        PhoneTag::class,
        PhoneLinkTag::class,
        WhatsAppTag::class,
        WhatsAppLinkTag::class,
        EmailTag::class,
        EmailLinkTag::class,
        AdminEmailTag::class,
        AddressTag::class,
        CityRegionTag::class,
        PostalCodeTag::class,
        CountryTag::class,
        FullAddressTag::class,
        HoursFormattedTag::class,
        WebsiteTag::class,
        GoogleMapsUrlTag::class,
        InstagramUrlTag::class,
        FacebookUrlTag::class,
        YoutubeUrlTag::class,
        LinkedinUrlTag::class,
        TiktokUrlTag::class,
        DoctoraliaUrlTag::class,
        LogoLightTag::class,
        LogoDarkTag::class,
        PrimaryColorTag::class,
        SecondaryColorTag::class,
        AccentColorTag::class,
    ];

    /** @var array<string, array{kind:string,path?:string,formatter?:string}> */
    private const UNIVERSAL_FIELDS = [
        'business.name' => ['kind' => 'text', 'path' => 'identity.business_name'],
        'business.type' => ['kind' => 'text', 'path' => 'identity.business_type'],
        'business.tagline' => ['kind' => 'text', 'path' => 'identity.tagline'],
        'business.tax_id' => ['kind' => 'text', 'path' => 'identity.tax_id'],
        'business.phone' => ['kind' => 'text', 'path' => 'contact.phone'],
        'business.phone_link' => ['kind' => 'url', 'formatter' => 'phone_link'],
        'business.whatsapp' => ['kind' => 'text', 'path' => 'contact.whatsapp.number'],
        'business.whatsapp_link' => ['kind' => 'url', 'formatter' => 'whatsapp_link'],
        'business.email' => ['kind' => 'text', 'path' => 'contact.email_public'],
        'business.email_link' => ['kind' => 'url', 'formatter' => 'email_link'],
        'business.admin_email' => ['kind' => 'text', 'path' => 'contact.email_admin'],
        'business.address.street' => ['kind' => 'text', 'path' => 'location.address'],
        'business.address.city_region' => ['kind' => 'text', 'path' => 'location.city_region'],
        'business.address.postal_code' => ['kind' => 'text', 'path' => 'location.postal_code'],
        'business.address.country' => ['kind' => 'text', 'path' => 'location.country'],
        'business.address.full' => ['kind' => 'multiline', 'formatter' => 'full_address'],
        'business.google_maps_url' => ['kind' => 'url', 'path' => 'location.google_maps_url'],
        'business.hours.formatted' => ['kind' => 'multiline', 'formatter' => 'hours_formatted'],
        'business.social.website' => ['kind' => 'url', 'path' => 'social.website_url'],
        'business.social.instagram' => ['kind' => 'url', 'path' => 'social.instagram'],
        'business.social.facebook' => ['kind' => 'url', 'path' => 'social.facebook'],
        'business.social.youtube' => ['kind' => 'url', 'path' => 'social.youtube'],
        'business.social.linkedin' => ['kind' => 'url', 'path' => 'social.linkedin'],
        'business.social.tiktok' => ['kind' => 'url', 'path' => 'social.tiktok'],
        'business.social.doctoralia' => ['kind' => 'url', 'path' => 'social.doctoralia'],
        'branding.logo_light' => ['kind' => 'image', 'path' => 'branding.logo_light_id'],
        'branding.logo_dark' => ['kind' => 'image', 'path' => 'branding.logo_dark_id'],
        'branding.primary_color' => ['kind' => 'color', 'path' => 'branding.primary_color'],
        'branding.secondary_color' => ['kind' => 'color', 'path' => 'branding.secondary_color'],
        'branding.accent_color' => ['kind' => 'color', 'path' => 'branding.accent_color'],
    ];

    public function __construct(
        private BusinessData $businessData,
        private LoggerInterface $logger
    ) {}

    public function register(): void
    {
        add_shortcode('flashsite', [$this, 'renderGenericShortcode']);
        add_shortcode('flashsite_business', [$this, 'renderGenericShortcode']);
        add_shortcode('flashsite_business_name', fn ($atts = []) => $this->renderTextShortcode('identity.business_name', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_business_type', fn ($atts = []) => $this->renderTextShortcode('identity.business_type', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_tax_id', fn ($atts = []) => $this->renderTextShortcode('identity.tax_id', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_phone', fn ($atts = []) => $this->renderTextShortcode('contact.phone', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_phone_link', fn ($atts = []) => $this->renderFormattedShortcode('phone_link', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_whatsapp', fn ($atts = []) => $this->renderTextShortcode('contact.whatsapp.number', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_whatsapp_link', fn ($atts = []) => $this->renderFormattedShortcode('whatsapp_link', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_email', fn ($atts = []) => $this->renderTextShortcode('contact.email_public', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_email_link', fn ($atts = []) => $this->renderFormattedShortcode('email_link', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_address', fn ($atts = []) => $this->renderTextShortcode('location.address', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_city_region', fn ($atts = []) => $this->renderTextShortcode('location.city_region', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_postal_code', fn ($atts = []) => $this->renderTextShortcode('location.postal_code', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_country', fn ($atts = []) => $this->renderTextShortcode('location.country', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_full_address', fn ($atts = []) => $this->renderFormattedShortcode('full_address', is_array($atts) ? $atts : [], true));
        add_shortcode('flashsite_google_maps_url', fn ($atts = []) => $this->renderTextShortcode('location.google_maps_url', is_array($atts) ? $atts : [], false, 'url'));
        add_shortcode('flashsite_hours', fn ($atts = []) => $this->renderFormattedShortcode('hours_formatted', is_array($atts) ? $atts : [], true));
        add_shortcode('flashsite_website', fn ($atts = []) => $this->renderTextShortcode('social.website_url', is_array($atts) ? $atts : [], false, 'url'));
        add_shortcode('flashsite_instagram', fn ($atts = []) => $this->renderTextShortcode('social.instagram', is_array($atts) ? $atts : [], false, 'url'));
        add_shortcode('flashsite_facebook', fn ($atts = []) => $this->renderTextShortcode('social.facebook', is_array($atts) ? $atts : [], false, 'url'));
        add_shortcode('flashsite_youtube', fn ($atts = []) => $this->renderTextShortcode('social.youtube', is_array($atts) ? $atts : [], false, 'url'));
        add_shortcode('flashsite_linkedin', fn ($atts = []) => $this->renderTextShortcode('social.linkedin', is_array($atts) ? $atts : [], false, 'url'));
        add_shortcode('flashsite_tiktok', fn ($atts = []) => $this->renderTextShortcode('social.tiktok', is_array($atts) ? $atts : [], false, 'url'));
        add_shortcode('flashsite_doctoralia', fn ($atts = []) => $this->renderTextShortcode('social.doctoralia', is_array($atts) ? $atts : [], false, 'url'));
        add_shortcode('flashsite_tagline', fn ($atts = []) => $this->renderTextShortcode('identity.tagline', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_logo_light', fn ($atts = []) => $this->renderLogoShortcode('branding.logo_light_id', is_array($atts) ? $atts : []));
        add_shortcode('flashsite_logo_dark', fn ($atts = []) => $this->renderLogoShortcode('branding.logo_dark_id', is_array($atts) ? $atts : []));
        add_action('elementor/dynamic_tags/register', [$this, 'registerElementorTags']);
    }

    public function boot(): void
    {
        $this->logger->info('Output Foundation module booted.');
    }

    public function isActive(): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return 'output-foundation';
    }

    /** @return list<class-string> */
    public static function getElementorTagClasses(): array
    {
        return self::ELEMENTOR_TAG_CLASSES;
    }

    /** @param array<string, mixed> $atts */
    public function renderGenericShortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'field' => '',
            'format' => '',
            'size' => 'full',
            'fallback' => '',
            'before' => '',
            'after' => '',
            'multiline' => 'no',
            'class' => 'flashsite-business-logo',
            'alt' => (string) $this->businessData->get('identity.business_name', ''),
            'link' => 'none',
            'width' => '',
            'height' => '',
        ], $atts, 'flashsite');

        $field = sanitize_text_field((string) $atts['field']);
        if ($field === '') {
            return '';
        }

        if (isset(self::UNIVERSAL_FIELDS[$field])) {
            $definition = self::UNIVERSAL_FIELDS[$field];
            $kind = (string) ($definition['kind'] ?? 'text');
            $path = (string) ($definition['path'] ?? '');
            $formatter = (string) ($definition['formatter'] ?? '');

            if ($kind === 'image') {
                return $this->renderLogoShortcode($path, $atts);
            }

            if ($formatter !== '') {
                return $this->renderFormattedShortcode($formatter, $atts, $kind === 'multiline');
            }

            return $this->renderTextShortcode($path, $atts, $kind === 'multiline', in_array($kind, ['url', 'color'], true) ? $kind : 'text');
        }

        if ($this->isBlockedPublicOutputPath($field)) {
            return '';
        }

        $format = sanitize_key((string) $atts['format']);
        if ($format === 'image') {
            return $this->renderLogoShortcode($field, $atts);
        }

        return $this->renderTextShortcode($field, $atts, strtolower((string) $atts['multiline']) === 'yes', $format === 'url' ? 'url' : ($format === 'color' ? 'color' : 'text'));
    }

    public function registerElementorTags($dynamicTags): void
    {
        if (! class_exists('\\Elementor\\Core\\DynamicTags\\Data_Tag') || ! method_exists($dynamicTags, 'register')) {
            return;
        }

        if (method_exists($dynamicTags, 'register_group')) {
            $dynamicTags->register_group('flashsite', [
                'title' => 'FlashSite Core',
            ]);
        }

        foreach (self::ELEMENTOR_TAG_CLASSES as $tagClass) {
            if (class_exists($tagClass)) {
                $dynamicTags->register(new $tagClass());
            }
        }
    }

    /** @param array<string, mixed> $atts */
    private function renderTextShortcode(string $path, array $atts = [], bool $multiline = false, string $kind = 'text'): string
    {
        $atts = shortcode_atts([
            'fallback' => '',
            'before' => '',
            'after' => '',
            'multiline' => 'no',
        ], $atts, 'flashsite');

        if ($path !== '_flashsite_virtual' && $this->isBlockedPublicOutputPath($path)) {
            return '';
        }

        $value = $path === '_flashsite_virtual' ? (string) ($atts['fallback'] ?? '') : $this->resolveValueAsString($path);
        if ($value === '') {
            $value = (string) $atts['fallback'];
        }
        if ($value === '') {
            return '';
        }

        $value = $this->normalizeLineEndings($value);
        $useMultiline = $multiline || strtolower((string) $atts['multiline']) === 'yes';
        $escaped = match ($kind) {
            'url' => esc_url(str_replace(["\r\n", "\n"], '', $value)),
            'color' => esc_html(str_replace(["\r\n", "\n"], '', $value)),
            default => $useMultiline ? nl2br(esc_html($value)) : esc_html(str_replace(["\r\n", "\n"], ' ', $value)),
        };

        return (string) $atts['before'] . $escaped . (string) $atts['after'];
    }

    /** @param array<string, mixed> $atts */
    private function renderFormattedShortcode(string $formatter, array $atts = [], bool $multiline = false): string
    {
        $value = match ($formatter) {
            'full_address' => $this->resolveFullAddress(),
            'hours_formatted' => $this->resolveOpeningHours(),
            'phone_link' => $this->resolvePhoneLink(),
            'email_link' => $this->resolveEmailLink(),
            'whatsapp_link' => $this->resolveWhatsAppLink(),
            default => '',
        };

        if ($value === '') {
            $value = (string) ($atts['fallback'] ?? '');
        }
        if ($value === '') {
            return '';
        }

        return $this->renderTextShortcode('_flashsite_virtual', array_merge($atts, ['fallback' => $value]), $multiline, in_array($formatter, ['phone_link', 'email_link', 'whatsapp_link'], true) ? 'url' : 'text');
    }

    /** @param array<string, mixed> $atts */
    private function renderLogoShortcode(string $path, array $atts = []): string
    {
        $atts = shortcode_atts([
            'size' => 'full',
            'class' => 'flashsite-business-logo',
            'alt' => (string) $this->businessData->get('identity.business_name', ''),
            'fallback' => '',
            'link' => 'none',
            'width' => '',
            'height' => '',
        ], $atts, 'flashsite');

        $attachmentId = absint($this->businessData->get($path, 0));
        if ($attachmentId <= 0) {
            return (string) $atts['fallback'];
        }

        $class = $this->sanitizeClassList((string) $atts['class']);
        $alt = sanitize_text_field((string) $atts['alt']);
        $width = absint((string) $atts['width']);
        $height = absint((string) $atts['height']);

        $size = sanitize_key((string) $atts['size']);
        if ($size === '') {
            $size = 'full';
        }

        $src = wp_get_attachment_image_url($attachmentId, $size);
        if (! is_string($src) || $src === '') {
            return (string) $atts['fallback'];
        }

        $meta = wp_get_attachment_metadata($attachmentId);
        $naturalWidth = is_array($meta) && isset($meta['width']) ? absint($meta['width']) : 0;
        $naturalHeight = is_array($meta) && isset($meta['height']) ? absint($meta['height']) : 0;

        $styles = ['max-width:100%'];
        $dimensions = [];

        if ($width > 0 && $height > 0) {
            $styles[] = 'width:' . $width . 'px';
            $styles[] = 'height:' . $height . 'px';
            $styles[] = 'object-fit:contain';
            $dimensions[] = 'width="' . $width . '"';
            $dimensions[] = 'height="' . $height . '"';
        } elseif ($width > 0) {
            $styles[] = 'width:' . $width . 'px';
            $styles[] = 'height:auto';
            $dimensions[] = 'width="' . $width . '"';
            if ($naturalWidth > 0 && $naturalHeight > 0) {
                $calculatedHeight = (int) round(($width / $naturalWidth) * $naturalHeight);
                if ($calculatedHeight > 0) {
                    $dimensions[] = 'height="' . $calculatedHeight . '"';
                }
            }
        } elseif ($height > 0) {
            $styles[] = 'height:' . $height . 'px';
            $styles[] = 'width:auto';
            $dimensions[] = 'height="' . $height . '"';
            if ($naturalWidth > 0 && $naturalHeight > 0) {
                $calculatedWidth = (int) round(($height / $naturalHeight) * $naturalWidth);
                if ($calculatedWidth > 0) {
                    $dimensions[] = 'width="' . $calculatedWidth . '"';
                }
            }
        } else {
            if ($naturalWidth > 0) {
                $dimensions[] = 'width="' . $naturalWidth . '"';
            }
            if ($naturalHeight > 0) {
                $dimensions[] = 'height="' . $naturalHeight . '"';
            }
        }

        $html = sprintf(
            '<img src="%1$s" alt="%2$s" class="%3$s" style="%4$s" loading="lazy" decoding="async" %5$s />',
            esc_url($src),
            esc_attr($alt),
            esc_attr($class),
            esc_attr(implode(';', $styles)),
            implode(' ', $dimensions)
        );

        $linkType = sanitize_key((string) $atts['link']);
        if ($linkType !== '') {
            $linkUrl = match ($linkType) {
                'website' => $this->resolveValueAsString('social.website_url'),
                'home' => home_url('/'),
                default => '',
            };

            if ($linkUrl !== '') {
                $html = sprintf(
                    '<a href="%1$s" class="flashsite-business-logo-link">%2$s</a>',
                    esc_url($linkUrl),
                    $html
                );
            }
        }

        return $html;
    }

    private function resolveValueAsString(string $path): string
    {
        $value = $this->businessData->get($path, '');

        if (is_array($value)) {
            $parts = array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $value));
            return implode(', ', $parts);
        }

        return trim((string) $value);
    }

    private function resolvePhoneLink(): string
    {
        $phone = trim((string) $this->businessData->get('contact.phone', ''));
        if ($phone === '') {
            return '';
        }

        $normalized = preg_replace('/[^0-9+]/', '', $phone) ?: '';
        return $normalized !== '' ? 'tel:' . $normalized : '';
    }

    private function resolveEmailLink(): string
    {
        $email = trim((string) $this->businessData->get('contact.email_public', ''));
        return $email !== '' ? 'mailto:' . $email : '';
    }

    private function resolveWhatsAppLink(): string
    {
        $link = trim((string) $this->businessData->get('contact.whatsapp.link', ''));
        if ($link !== '') {
            return $link;
        }

        $number = trim((string) $this->businessData->get('contact.whatsapp.number', ''));
        if ($number === '') {
            return '';
        }

        $normalized = preg_replace('/\D+/', '', $number) ?: '';
        return $normalized !== '' ? 'https://wa.me/' . $normalized : '';
    }

    private function resolveFullAddress(): string
    {
        $parts = array_filter([
            trim((string) $this->businessData->get('location.address', '')),
            trim((string) $this->businessData->get('location.city_region', '')),
            trim((string) $this->businessData->get('location.postal_code', '')),
            trim((string) $this->businessData->get('location.country', '')),
        ], static fn (string $value): bool => $value !== '');

        return implode(', ', $parts);
    }

    private function resolveOpeningHours(): string
    {
        $ordered = [
            'Seg' => (string) $this->businessData->get('hours.monday', ''),
            'Ter' => (string) $this->businessData->get('hours.tuesday', ''),
            'Qua' => (string) $this->businessData->get('hours.wednesday', ''),
            'Qui' => (string) $this->businessData->get('hours.thursday', ''),
            'Sex' => (string) $this->businessData->get('hours.friday', ''),
            'Sáb' => (string) $this->businessData->get('hours.saturday', ''),
            'Dom' => (string) $this->businessData->get('hours.sunday', ''),
        ];
        $lines = [];
        foreach ($ordered as $label => $value) {
            $value = trim($value);
            if ($value !== '') {
                $lines[] = $label . ': ' . $value;
            }
        }
        $notes = trim((string) $this->businessData->get('hours.notes', ''));
        if ($notes !== '') {
            $lines[] = $notes;
        }
        if ($lines === []) {
            return trim((string) $this->businessData->get('hours.legacy_text', ''));
        }

        return implode("\n", $lines);
    }

    private function normalizeLineEndings(string $value): string
    {
        return str_replace(["\r\n", "\r"], "\n", trim($value));
    }

    private function sanitizeClassList(string $classList): string
    {
        $classes = preg_split('/\s+/', trim($classList)) ?: [];
        $classes = array_filter(array_map('sanitize_html_class', $classes));
        return implode(' ', $classes);
    }

    private function isBlockedPublicOutputPath(string $path): bool
    {
        return isset(self::BLOCKED_PUBLIC_OUTPUT_PATHS[$path]);
    }
}
