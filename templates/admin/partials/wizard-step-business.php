<?php
declare(strict_types=1);

$profileData = $businessProfile->toArray();
$errors = is_array($errors ?? null) ? $errors : [];
$fieldError = static function (string $key, array $errors): string {
    return isset($errors[$key]) ? (string) $errors[$key] : '';
};
$fieldValue = static function (array $data, array $path): string {
    $current = $data;
    foreach ($path as $segment) {
        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            return '';
        }
        $current = $current[$segment];
    }
    if (is_scalar($current)) {
        return (string) $current;
    }
    return '';
};
$listValue = static function (array $data, array $path): string {
    $current = $data;
    foreach ($path as $segment) {
        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            return '';
        }
        $current = $current[$segment];
    }
    return is_array($current) ? implode("\n", array_map('strval', $current)) : '';
};

$mediaPreview = static function (int $attachmentId): string {
    if ($attachmentId <= 0) {
        return '<span class="description">Nenhum ficheiro seleccionado.</span>';
    }

    $thumb = wp_get_attachment_image($attachmentId, 'medium', false, [
        'loading' => 'lazy',
        'decoding' => 'async',
        'alt' => '',
    ]);
    if (is_string($thumb) && $thumb !== '') {
        $filename = wp_basename((string) get_attached_file($attachmentId));
        return $thumb . '<div class="fsc-media-preview__meta">' . esc_html($filename !== '' ? $filename : ('Attachment ID ' . (string) $attachmentId)) . '</div>';
    }

    return '<span class="description">Attachment ID ' . esc_html((string) $attachmentId) . '</span>';
};
?>
<div class="fsc-card fsc-business-form-card">
    <h2>Dados do Negócio</h2>
    <p class="description">Preencha a base operacional do site com exemplos práticos em cada campo. Pode voltar depois para ajustar sem perder o progresso.</p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('flashsite_save_wizard_step'); ?>
        <input type="hidden" name="action" value="flashsite_save_wizard_step" />
        <input type="hidden" name="step" value="business-data" />

        <div class="fsc-form-section">
            <h3>Identificação</h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="business_name">Nome do negócio</label></th>
                    <td>
                        <input class="regular-text" id="business_name" name="business_name" placeholder="Ex: Flash Site Studio" value="<?php echo esc_attr($fieldValue($profileData, ['identity', 'business_name'])); ?>" />
                        <p class="description">Nome principal que identifica a empresa ou marca no site.</p>
                        <?php if ($fieldError('business_name', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('business_name', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="business_type">Área de atuação</label></th>
                    <td>
                        <select id="business_type" name="business_type">
                            <?php $businessType = $fieldValue($profileData, ['identity', 'business_type']); ?>
                            <option value="" <?php selected($businessType, ''); ?>>Selecione</option>
                            <option value="saude" <?php selected($businessType, 'saude'); ?>>Saúde</option>
                            <option value="direito" <?php selected($businessType, 'direito'); ?>>Direito</option>
                            <option value="arquitetura" <?php selected($businessType, 'arquitetura'); ?>>Arquitetura &amp; Design</option>
                            <option value="negocios" <?php selected($businessType, 'negocios'); ?>>Negócios &amp; Serviços</option>
                        </select>
                        <p class="description">Serve de base para futuras expansões do perfil do negócio.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="tagline">Tagline / slogan</label></th>
                    <td>
                        <input class="regular-text" id="tagline" name="tagline" placeholder="Ex: Websites rápidos, seguros e prontos para vender." value="<?php echo esc_attr($fieldValue($profileData, ['identity', 'tagline'])); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="nif">NIF</label></th>
                    <td>
                        <input class="regular-text" id="nif" name="nif" placeholder="Ex: 123 456 789" value="<?php echo esc_attr($fieldValue($profileData, ['identity', 'tax_id'])); ?>" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="fsc-form-section">
            <h3>Contacto</h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="phone">Telefone</label></th>
                    <td>
                        <input class="regular-text" id="phone" name="phone" placeholder="Ex: 351 912 345 678" value="<?php echo esc_attr($fieldValue($profileData, ['contact', 'phone'])); ?>" />
                        <p class="description">Número principal de atendimento do negócio.</p>
                        <?php if ($fieldError('phone', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('phone', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="whatsapp">WhatsApp</label></th>
                    <td>
                        <input class="regular-text" id="whatsapp" name="whatsapp" placeholder="Ex: 351 912 345 678" value="<?php echo esc_attr($fieldValue($profileData, ['contact', 'whatsapp', 'number'])); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="whatsapp_link">Link WhatsApp</label></th>
                    <td>
                        <input class="regular-text" id="whatsapp_link" name="whatsapp_link" type="url" placeholder="Ex: https://wa.me/351912345678" value="<?php echo esc_attr($fieldValue($profileData, ['contact', 'whatsapp', 'link'])); ?>" />
                        <?php if ($fieldError('whatsapp_link', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('whatsapp_link', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="email">Email público</label></th>
                    <td>
                        <input class="regular-text" id="email" name="email" type="email" placeholder="Ex: contacto@flashsite.pt" value="<?php echo esc_attr($fieldValue($profileData, ['contact', 'email_public'])); ?>" />
                        <?php if ($fieldError('email', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('email', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="email_admin">Email administrativo</label></th>
                    <td>
                        <input class="regular-text" id="email_admin" name="email_admin" type="email" placeholder="Ex: admin@flashsite.pt" value="<?php echo esc_attr($fieldValue($profileData, ['contact', 'email_admin'])); ?>" />
                        <?php if ($fieldError('email_admin', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('email_admin', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="fsc-form-section">
            <h3>Localização</h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="address">Morada</label></th>
                    <td>
                        <textarea class="large-text" id="address" name="address" rows="3" placeholder="Ex: Rua Exemplo, 25&#10;2.º andar, Sala 4"><?php echo esc_textarea($fieldValue($profileData, ['location', 'address'])); ?></textarea>
                        <p class="description">Apenas rua, número e complemento. Cidade e código postal têm campos próprios abaixo.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="city_region">Cidade / região</label></th>
                    <td>
                        <input class="regular-text" id="city_region" name="city_region" placeholder="Ex: Lisboa, PT" value="<?php echo esc_attr($fieldValue($profileData, ['location', 'city_region'])); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="postal_code">Código postal</label></th>
                    <td>
                        <input class="regular-text" id="postal_code" name="postal_code" placeholder="Ex: 1000-100" value="<?php echo esc_attr($fieldValue($profileData, ['location', 'postal_code'])); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="country">País</label></th>
                    <td>
                        <input class="regular-text" id="country" name="country" placeholder="Ex: Portugal" value="<?php echo esc_attr($fieldValue($profileData, ['location', 'country'])); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="google_maps_url">Link Google Maps</label></th>
                    <td>
                        <input class="regular-text" id="google_maps_url" name="google_maps_url" type="url" placeholder="Ex: https://maps.app.goo.gl/..." value="<?php echo esc_attr($fieldValue($profileData, ['location', 'google_maps_url'])); ?>" />
                        <?php if ($fieldError('google_maps_url', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('google_maps_url', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="google_maps_embed">Embed Google Maps</label></th>
                    <td>
                        <textarea class="large-text code" id="google_maps_embed" name="google_maps_embed" rows="4" placeholder="Cole aqui o iframe do Google Maps"><?php echo esc_textarea($fieldValue($profileData, ['location', 'google_maps_embed'])); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>

        <div class="fsc-form-section">
            <h3>Presença digital</h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="website_url">Website</label></th>
                    <td>
                        <input class="regular-text" id="website_url" name="website_url" type="url" placeholder="Ex: https://www.flashsite.pt" value="<?php echo esc_attr($fieldValue($profileData, ['social', 'website_url'])); ?>" />
                        <?php if ($fieldError('website_url', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('website_url', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <?php foreach ([
                    'instagram' => 'Instagram',
                    'facebook' => 'Facebook',
                    'youtube' => 'YouTube',
                    'linkedin' => 'LinkedIn',
                    'tiktok' => 'TikTok',
                    'doctoralia' => 'Doctoralia / iClinic',
                ] as $socialKey => $socialLabel) : ?>
                    <tr>
                        <th><label for="social_<?php echo esc_attr($socialKey); ?>"><?php echo esc_html($socialLabel); ?></label></th>
                        <td>
                            <input class="regular-text" id="social_<?php echo esc_attr($socialKey); ?>" name="social_<?php echo esc_attr($socialKey); ?>" type="url" placeholder="Ex: https://<?php echo esc_attr($socialKey); ?>.com/perfil" value="<?php echo esc_attr($fieldValue($profileData, ['social', $socialKey])); ?>" />
                            <?php if ($fieldError('social_' . $socialKey, $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('social_' . $socialKey, $errors)); ?></p><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="fsc-form-section">
            <h3>Horários</h3>
            <table class="form-table" role="presentation">
                <?php foreach ([
                    'monday' => 'Segunda-feira',
                    'tuesday' => 'Terça-feira',
                    'wednesday' => 'Quarta-feira',
                    'thursday' => 'Quinta-feira',
                    'friday' => 'Sexta-feira',
                    'saturday' => 'Sábado',
                    'sunday' => 'Domingo',
                ] as $dayKey => $dayLabel) : ?>
                    <tr>
                        <th><label for="hours_<?php echo esc_attr($dayKey); ?>"><?php echo esc_html($dayLabel); ?></label></th>
                        <td>
                            <input class="regular-text" id="hours_<?php echo esc_attr($dayKey); ?>" name="hours_<?php echo esc_attr($dayKey); ?>" placeholder="Ex: 09h00 às 18h00" value="<?php echo esc_attr($fieldValue($profileData, ['hours', $dayKey])); ?>" />
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th><label for="hours_notes">Observações</label></th>
                    <td>
                        <textarea class="large-text" id="hours_notes" name="hours_notes" rows="3" placeholder="Ex: Atendimento apenas com agendamento prévio."><?php echo esc_textarea($fieldValue($profileData, ['hours', 'notes'])); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="opening_hours">Texto livre legado</label></th>
                    <td>
                        <textarea class="large-text" id="opening_hours" name="opening_hours" rows="3" placeholder="Opcional. Mantém compatibilidade com conteúdo antigo."><?php echo esc_textarea($fieldValue($profileData, ['hours', 'legacy_text'])); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>

        <div class="fsc-form-section">
            <h3>Dados profissionais</h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="professional_display_name">Nome de exibição</label></th>
                    <td><input class="regular-text" id="professional_display_name" name="professional_display_name" placeholder="Ex: Dra. Ana Silva" value="<?php echo esc_attr($fieldValue($profileData, ['professional', 'display_name'])); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="professional_title">Título profissional</label></th>
                    <td><input class="regular-text" id="professional_title" name="professional_title" placeholder="Ex: Advogada especialista em direito fiscal" value="<?php echo esc_attr($fieldValue($profileData, ['professional', 'title'])); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="professional_specialty">Especialidade</label></th>
                    <td><input class="regular-text" id="professional_specialty" name="professional_specialty" placeholder="Ex: Oftalmologia, Direito do Trabalho, Interiores" value="<?php echo esc_attr($fieldValue($profileData, ['professional', 'specialty'])); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="professional_license">Registo / licença</label></th>
                    <td><input class="regular-text" id="professional_license" name="professional_license" placeholder="Ex: CRM 12345, OA 67890" value="<?php echo esc_attr($fieldValue($profileData, ['professional', 'license'])); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="professional_secondary_id">ID secundário</label></th>
                    <td><input class="regular-text" id="professional_secondary_id" name="professional_secondary_id" placeholder="Ex: RQE 123, CAU A12345" value="<?php echo esc_attr($fieldValue($profileData, ['professional', 'secondary_id'])); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="professional_services">Serviços oferecidos</label></th>
                    <td><textarea class="large-text" id="professional_services" name="professional_services" rows="4" placeholder="Um serviço por linha"><?php echo esc_textarea($listValue($profileData, ['professional', 'services'])); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="professional_accepted_plans">Planos / convênios</label></th>
                    <td><textarea class="large-text" id="professional_accepted_plans" name="professional_accepted_plans" rows="4" placeholder="Um item por linha"><?php echo esc_textarea($listValue($profileData, ['professional', 'accepted_plans'])); ?></textarea></td>
                </tr>
            </table>
        </div>


        <div class="fsc-form-section">
            <h3>Branding</h3>
            <p class="description">Fonte central para logos, cores e tipografia. Esta camada serve shortcodes e dynamic tags sem depender do Elementor como origem dos dados.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="branding_logo_light_id">Logo para fundo claro</label></th>
                    <td>
                        <div class="fsc-media-field">
                            <input type="hidden" id="branding_logo_light_id" name="branding_logo_light_id" value="<?php echo esc_attr((string) $fieldValue($profileData, ['branding', 'logo_light_id'])); ?>" />
                            <div id="branding_logo_light_preview" class="fsc-media-preview"><?php echo $mediaPreview((int) $fieldValue($profileData, ['branding', 'logo_light_id'])); ?></div>
                            <div class="fsc-media-field__actions">
                                <button type="button" class="button fsc-media-button" data-target="branding_logo_light_id" data-preview="branding_logo_light_preview">Selecionar ficheiro</button>
                                <button type="button" class="button-link-delete fsc-media-remove" data-target="branding_logo_light_id" data-preview="branding_logo_light_preview">Remover</button>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="branding_logo_dark_id">Logo para fundo escuro</label></th>
                    <td>
                        <div class="fsc-media-field">
                            <input type="hidden" id="branding_logo_dark_id" name="branding_logo_dark_id" value="<?php echo esc_attr((string) $fieldValue($profileData, ['branding', 'logo_dark_id'])); ?>" />
                            <div id="branding_logo_dark_preview" class="fsc-media-preview"><?php echo $mediaPreview((int) $fieldValue($profileData, ['branding', 'logo_dark_id'])); ?></div>
                            <div class="fsc-media-field__actions">
                                <button type="button" class="button fsc-media-button" data-target="branding_logo_dark_id" data-preview="branding_logo_dark_preview">Selecionar ficheiro</button>
                                <button type="button" class="button-link-delete fsc-media-remove" data-target="branding_logo_dark_id" data-preview="branding_logo_dark_preview">Remover</button>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="branding_primary_color">Cor principal</label></th>
                    <td>
                        <input class="regular-text fsc-color-field" id="branding_primary_color" name="branding_primary_color" value="<?php echo esc_attr($fieldValue($profileData, ['branding', 'primary_color'])); ?>" placeholder="#0F172A" />
                        <?php if ($fieldError('branding_primary_color', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('branding_primary_color', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="branding_secondary_color">Cor secundária</label></th>
                    <td>
                        <input class="regular-text fsc-color-field" id="branding_secondary_color" name="branding_secondary_color" value="<?php echo esc_attr($fieldValue($profileData, ['branding', 'secondary_color'])); ?>" placeholder="#334155" />
                        <?php if ($fieldError('branding_secondary_color', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('branding_secondary_color', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="branding_accent_color">Cor de destaque</label></th>
                    <td>
                        <input class="regular-text fsc-color-field" id="branding_accent_color" name="branding_accent_color" value="<?php echo esc_attr($fieldValue($profileData, ['branding', 'accent_color'])); ?>" placeholder="#22C55E" />
                        <?php if ($fieldError('branding_accent_color', $errors) !== '') : ?><p class="fsc-field-error"><?php echo esc_html($fieldError('branding_accent_color', $errors)); ?></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="branding_font_primary">Fonte principal</label></th>
                    <td><input class="regular-text" id="branding_font_primary" name="branding_font_primary" placeholder="Ex: Inter" value="<?php echo esc_attr($fieldValue($profileData, ['branding', 'font_primary'])); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="branding_font_secondary">Fonte secundária</label></th>
                    <td><input class="regular-text" id="branding_font_secondary" name="branding_font_secondary" placeholder="Ex: Playfair Display" value="<?php echo esc_attr($fieldValue($profileData, ['branding', 'font_secondary'])); ?>" /></td>
                </tr>
            </table>
        </div>

        <p><button type="submit" class="button button-primary">Guardar e continuar</button></p>
    </form>
</div>
