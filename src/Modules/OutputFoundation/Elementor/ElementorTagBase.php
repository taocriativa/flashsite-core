<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\OutputFoundation\Elementor;

/**
 * ElementorTagBase.php
 *
 * Compatibilidade condicional com Elementor 3.x e 3.21+.
 *
 * No Elementor < 3.21, a classe base é Data_Tag.
 * No Elementor >= 3.21, Data_Tag foi removida; a base passa a ser Tag.
 *
 * @since 2.4.0
 */

if (! class_exists(__NAMESPACE__ . '\\ElementorTagBase', false)) {
    if (class_exists('\\Elementor\\Core\\DynamicTags\\Data_Tag')) {
        // Elementor < 3.21
        abstract class ElementorTagBase extends \Elementor\Core\DynamicTags\Data_Tag
        {
        }
    } elseif (class_exists('\\Elementor\\Core\\DynamicTags\\Tag')) {
        // Elementor >= 3.21
        abstract class ElementorTagBase extends \Elementor\Core\DynamicTags\Tag
        {
        }
    } else {
        // Elementor não carregado — classe stub que nunca será instanciada
        abstract class ElementorTagBase
        {
        }
    }
}
