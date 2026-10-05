<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Contrato de um preset de Coleção (tipo de conteúdo repetível por setor).
 *
 * @since 2.6.0
 */
interface CollectionPresetInterface
{
    /** Chave do preset, ex.: "imovel". */
    public function key(): string;

    /** Post type WP, ex.: "fs_imovel" (máx. 20 caracteres). */
    public function postType(): string;

    /** Slug público do arquivo, ex.: "imoveis". */
    public function slug(): string;

    /** @return array<string, string> singular, plural, add_new, ... */
    public function labels(): array;

    /** @return list<TaxonomyDefinition> ordenadas */
    public function taxonomies(): array;

    /** @return list<FieldDefinition> ordenados */
    public function fields(): array;

    public function field(string $key): ?FieldDefinition;

    /** @return list<FieldDefinition> só os públicos */
    public function publicFields(): array;

    /** @return array<string, string> chave => título, pela ordem da ficha */
    public function groups(): array;

    /** Tipo schema.org para JSON-LD, ou null. */
    public function schemaType(): ?string;

    /** @return list<string> */
    public function supports(): array;

    public function menuIcon(): string;

    /** Capability base: singular e plural para capability_type. */
    public function capabilitySingular(): string;
    public function capabilityPlural(): string;

    /** Configuração específica do preset (ex.: faixas de preço, ordenação). */
    public function setting(string $key, mixed $default = null): mixed;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
