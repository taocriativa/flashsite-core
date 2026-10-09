<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\ClientPanel;

/**
 * Painel em português do Brasil nos sites do mercado BR (@since 3.1.0).
 *
 * Os textos do painel do Core são escritos em PT-PT. Nos sites BR, as telas do FlashSite e das
 * coleções passam por esta troca de palavras antes de chegar ao navegador. Só troca palavras
 * inteiras e nunca mexe em <script>, <style> nem em nomes de campos e endereços.
 */
final class PtBrAdmin
{
    /** PT-PT => PT-BR. Expressões antes das palavras soltas. */
    public const MAP = [
        'A sua conta' => 'Sua conta',
        'carregue em' => 'clique em',
        'Carregue em' => 'Clique em',
        'está a ser' => 'está sendo',
        'estão a ser' => 'estão sendo',
        'código postal' => 'CEP',
        'Código postal' => 'CEP',
        'Código Postal' => 'CEP',
        'palavra-passe' => 'senha',
        'Palavra-passe' => 'Senha',
        'guardar' => 'salvar',
        'Guardar' => 'Salvar',
        'guardado' => 'salvo',
        'guardada' => 'salva',
        'guardados' => 'salvos',
        'guardadas' => 'salvas',
        'Guardado' => 'Salvo',
        'Guardada' => 'Salva',
        'registo' => 'registro',
        'registos' => 'registros',
        'Registo' => 'Registro',
        'registado' => 'registrado',
        'registada' => 'registrada',
        'registados' => 'registrados',
        'registadas' => 'registradas',
        'registar' => 'registrar',
        'ficheiro' => 'arquivo',
        'ficheiros' => 'arquivos',
        'Ficheiro' => 'Arquivo',
        'Ficheiros' => 'Arquivos',
        'morada' => 'endereço',
        'Morada' => 'Endereço',
        'contacto' => 'contato',
        'contactos' => 'contatos',
        'Contacto' => 'Contato',
        'Contactos' => 'Contatos',
        'definições' => 'configurações',
        'Definições' => 'Configurações',
        'telemóvel' => 'celular',
        'Telemóvel' => 'Celular',
        'ecrã' => 'tela',
        'encontrámos' => 'encontramos',
        'Encontrámos' => 'Encontramos',
        'utilizador' => 'usuário',
        'utilizadores' => 'usuários',
        'Utilizador' => 'Usuário',
        'Utilizadores' => 'Usuários',
        'equipa' => 'equipe',
        'Equipa' => 'Equipe',
        'secção' => 'seção',
        'secções' => 'seções',
        'Secção' => 'Seção',
        'Secções' => 'Seções',
        'gerir' => 'gerenciar',
        'Gerir' => 'Gerenciar',
        'activo' => 'ativo',
        'activa' => 'ativa',
        'actualizar' => 'atualizar',
        'actualizado' => 'atualizado',
        'actualização' => 'atualização',
        'seleccionado' => 'selecionado',
        'seleccionar' => 'selecionar',
        'facto' => 'fato',
        'por publicar' => 'não publicados',
    ];

    public static function convert(string $html): string
    {
        // Partes que não se tocam: scripts, estilos e atributos técnicos (name, id, href, src, value de campos ocultos).
        $parts = preg_split('/(<script\b.*?<\/script>|<style\b.*?<\/style>|<textarea\b.*?<\/textarea>|\s(?:name|id|href|src|action|for|class|data-[\w-]+)="[^"]*")/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($parts)) {
            return $html;
        }
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                continue; // delimitador capturado: fica como está
            }
            $parts[$i] = self::convertText($part);
        }
        return implode('', $parts);
    }

    public static function convertText(string $text): string
    {
        static $pattern = null;
        if ($pattern === null) {
            $words = array_map(static fn (string $w): string => preg_quote($w, '/'), array_keys(self::MAP));
            $pattern = '/(?<![\p{L}\p{N}_\-])(' . implode('|', $words) . ')(?![\p{L}\p{N}_\-])/u';
        }
        $result = preg_replace_callback($pattern, static fn (array $m): string => self::MAP[$m[1]] ?? $m[1], $text);
        return is_string($result) ? $result : $text;
    }
}
