<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';
require_once __DIR__ . '/../Support/WpPostStubs.php';
require_once __DIR__ . '/../Support/AbilitiesFixture.php';

/** Abilities de itens: leitura, validação, criação, patch, publicação e lixo (@since 3.5.0). */
final class AbilitiesItemsTest extends TestCase
{
    private AbilitiesFixture $fx;

    public function run(): void
    {
        $this->fx = new AbilitiesFixture('PT', [dirname(__DIR__) . '/fixtures/abilities_ext']);
        $fx = $this->fx;

        // Coleção inativa: nada escreve.
        $fx->asUser(['edit_fs_servico_items']);
        $inactive = $fx->call('flashsite/create-item', ['collection' => 'servico', 'title' => 'x']);
        $this->assertSame('collection_inactive', $inactive->get_error_code());
        $this->assertSame(409, $inactive->get_error_data()['status']);

        $fx->activate('imovel', 'prato', 'x_ficha');
        $this->asManager();
        $this->createReadUpdate();
        $this->validationWithoutWrites();
        $this->rejections();
        $this->publishing();
        $this->requiredTaxonomyAndBools();
        $this->ownershipAndTrash();
        $this->listing();
    }

    private function asManager(array $extra = [], array $without = []): void
    {
        $caps = array_merge(
            ['flashsite_manage_collections'],
            AbilitiesFixture::managerCaps('fs_imovel_items'),
            AbilitiesFixture::managerCaps('fs_prato_items'),
            AbilitiesFixture::managerCaps('fs_x_ficha_items'),
            $extra
        );
        $this->fx->asUser(array_values(array_diff($caps, $without)));
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        return [serialize($GLOBALS['flashsite_test_posts']), $GLOBALS['flashsite_test_post_meta'], $GLOBALS['flashsite_test_object_terms'], $GLOBALS['flashsite_test_terms']];
    }

    private function postCount(): int
    {
        return count($GLOBALS['flashsite_test_posts']);
    }

    private function createReadUpdate(): void
    {
        $fx = $this->fx;
        $created = $fx->call('flashsite/create-item', [
            'collection' => 'imovel',
            'title' => 'T2 em Alvalade',
            'content' => 'Descrição do imóvel',
            'fields' => ['referencia' => 'IMO-1', 'preco' => '285.000,00', 'destaque' => true, 'galeria' => [11, 12], 'classe_energetica' => 'b'],
            'taxonomies' => ['finalidade' => 'venda', 'tipo' => 'apartamento', 'zona' => 'Alvalade'],
        ]);
        $this->assertTrue(is_array($created), is_object($created) ? $created->get_error_message() : '');
        $this->assertTrue($created['created']);
        $item = $created['item'];
        $id = $item['id'];
        $this->assertSame('draft', $item['status'], 'Nunca publica implicitamente.');
        $this->assertSame('imovel', $item['collection']);
        $this->assertSame(285000.0, $item['fields']['preco']);
        $this->assertTrue($item['fields']['destaque']);
        $this->assertSame([11, 12], $item['fields']['galeria']);
        $this->assertSame('venda', $item['taxonomies']['finalidade'][0]['slug']);
        $this->assertSame('Alvalade', $item['taxonomies']['zona'][0]['name'], 'Termo novo criado em taxonomia não fixa.');
        $this->assertSame('disponivel', $item['taxonomies']['estado'][0]['slug'], 'Termo por defeito do preset.');
        $this->assertSame('Descrição do imóvel', $item['content']);
        $this->assertSame(11, $GLOBALS['flashsite_test_thumbnails'][$id], 'cover_from: primeira foto da galeria é a capa.');
        // O agente usa chaves de campo; a meta key é interna e foi gravada pelo pipeline.
        $this->assertSame(285000.0, $GLOBALS['flashsite_test_post_meta'][$id]['fs_preco']);
        $this->assertFalse(isset($item['fields']['fs_preco']));
        $band0 = $GLOBALS['flashsite_test_object_terms'][$id]['fs_imovel_faixa'] ?? [];
        $this->assertTrue($band0 !== [], 'Faixa de preço automática.');
        $this->assertSame([], $created['incomplete']);

        $got = $fx->call('flashsite/get-item', ['collection' => 'imovel', 'id' => $id]);
        $this->assertSame($item['fields'], $got['fields']);

        // Patch: omitidos preservados, bool incluído.
        $patched = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $id, 'fields' => ['quartos' => 3]]);
        $this->assertSame(['quartos'], $patched['changed_fields']);
        $this->assertSame(3.0, $patched['item']['fields']['quartos']);
        $this->assertSame(285000.0, $patched['item']['fields']['preco']);
        $this->assertTrue($patched['item']['fields']['destaque'], 'Bool omitido não é desligado.');
        $this->assertSame('IMO-1', $patched['item']['fields']['referencia']);
        $this->assertSame('venda', $patched['item']['taxonomies']['finalidade'][0]['slug'], 'Termos omitidos preservados.');
        $this->assertSame('T2 em Alvalade', $patched['item']['title']);

        // Baixa de preço e faixa.
        $drop = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $id, 'fields' => ['preco' => 250000]]);
        $this->assertSame(285000.0, $drop['item']['fields']['preco_anterior'], 'Preço anterior guardado pelo Core.');
        $this->assertSame(['preco'], $drop['changed_fields']);
        $cheaper = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $id, 'fields' => ['preco' => 150000]]);
        $this->assertTrue($GLOBALS['flashsite_test_object_terms'][$id]['fs_imovel_faixa'] !== $band0, 'A faixa acompanha o novo preço.');
        $this->assertSame(150000.0, $cheaper['item']['fields']['preco']);

        // Limpar um campo de propósito.
        $cleared = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $id, 'fields' => ['classe_energetica' => '']]);
        $this->assertSame('', $cleared['item']['fields']['classe_energetica']);
        // Título e slug.
        $renamed = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $id, 'title' => 'T2 renovado', 'slug' => 'T2 Renovado']);
        $this->assertSame('t2-renovado', $renamed['item']['slug']);
        $this->assertSame('T2 renovado', $renamed['item']['title']);
    }

    private function validationWithoutWrites(): void
    {
        $fx = $this->fx;
        $id = array_key_first($GLOBALS['flashsite_test_posts']);
        $before = $this->snapshot();

        $bad = $fx->call('flashsite/validate-item', ['collection' => 'imovel', 'id' => $id, 'fields' => ['preco' => 'abc', 'foo' => 1, 'classe_energetica' => 'zzz']]);
        $this->assertFalse($bad['valid']);
        $this->assertSame('invalid_value', $bad['errors']['preco']['code']);
        $this->assertSame('unknown_field', $bad['errors']['foo']['code']);
        $this->assertSame('invalid_value', $bad['errors']['classe_energetica']['code']);

        $good = $fx->call('flashsite/validate-item', ['collection' => 'imovel', 'id' => $id, 'fields' => ['preco' => '300.000,50']]);
        $this->assertTrue($good['valid']);
        $this->assertSame(300000.5, $good['normalized']['preco']);

        $incomplete = $fx->call('flashsite/validate-item', ['collection' => 'imovel', 'fields' => ['referencia' => 'X']]);
        $this->assertFalse($incomplete['valid'], 'Sem preço nem "sob consulta" (regra do preset).');
        $this->assertTrue(isset($incomplete['errors']['preco']));
        $missing = $fx->call('flashsite/validate-item', ['collection' => 'imovel', 'id' => 999999, 'fields' => []]);
        $this->assertSame('item_not_found', $missing->get_error_code());

        $this->assertTrue($before === $this->snapshot(), 'validate-item não escreve nada.');
    }

    private function rejections(): void
    {
        $fx = $this->fx;
        $count = $this->postCount();

        $unknown = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x', 'fields' => ['prec0' => 1]]);
        $this->assertSame('validation_failed', $unknown->get_error_code());
        $this->assertSame('unknown_field', $unknown->get_error_data()['errors']['prec0']['code'], 'Erro de digitação rejeitado, não ignorado.');
        $this->assertSame(422, $unknown->get_error_data()['status']);

        $invalid = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x', 'fields' => ['classe_energetica' => 'zzz']]);
        $this->assertSame('invalid_value', $invalid->get_error_data()['errors']['classe_energetica']['code']);

        $range = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x', 'status' => 'publish', 'fields' => ['preco' => -5, 'referencia' => 'A']]);
        $this->assertSame('validation_failed', $range->get_error_code(), 'Mínimo do campo respeitado ao publicar.');

        $locked = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x', 'taxonomies' => ['finalidade' => 'xyz']]);
        $this->assertSame('unknown_term', $locked->get_error_data()['errors']['tax_finalidade']['code'], 'Termos fixos não se criam.');
        $auto = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x', 'taxonomies' => ['faixa' => 'x']]);
        $this->assertSame('taxonomy_auto', $auto->get_error_data()['errors']['tax_faixa']['code']);
        $badTax = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x', 'taxonomies' => ['nada' => 'x']]);
        $this->assertSame('unknown_taxonomy', $badTax->get_error_data()['errors']['tax_nada']['code']);
        $many = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x', 'taxonomies' => ['finalidade' => ['venda', 'arrendamento']]]);
        $this->assertSame('too_many_terms', $many->get_error_data()['errors']['tax_finalidade']['code']);

        $noTitle = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => '  ']);
        $this->assertSame('validation_failed', $noTitle->get_error_code());
        $badStatus = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x', 'status' => 'trash']);
        $this->assertSame('invalid_status', $badStatus->get_error_code());
        $none = $fx->call('flashsite/create-item', ['collection' => 'inexistente', 'title' => 'x']);
        $this->assertSame('collection_not_found', $none->get_error_code());

        $this->assertSame($count, $this->postCount(), 'Pedidos rejeitados não gravam nada.');

        // Sem capability: negado antes de qualquer lógica.
        $fx->asUser([]);
        $this->assertSame('ability_invalid_permissions', $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x'])->get_error_code());
        $fx->asUser(['edit_fs_prato_items']);
        $this->assertSame('ability_invalid_permissions', $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'x'])->get_error_code());
        $this->asManager();
    }

    private function publishing(): void
    {
        $fx = $this->fx;
        $count = $this->postCount();
        $input = ['collection' => 'imovel', 'title' => 'Moradia', 'status' => 'publish', 'fields' => ['referencia' => 'IMO-2', 'preco' => 500000]];

        // Sem capability de publicar.
        $this->asManager([], ['publish_fs_imovel_items']);
        $denied = $fx->call('flashsite/create-item', $input);
        $this->assertSame('forbidden_publish', $denied->get_error_code());
        $this->assertSame(403, $denied->get_error_data()['status']);
        $this->assertSame($count, $this->postCount());
        // Mas rascunho continua a ser possível.
        $draft = $fx->call('flashsite/create-item', ['status' => 'draft'] + $input);
        $this->assertSame('draft', $draft['item']['status']);
        $promote = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $draft['item']['id'], 'status' => 'publish']);
        $this->assertSame('forbidden_publish', $promote->get_error_code());

        // Com capability e ficha íntegra: publica.
        $this->asManager();
        $published = $fx->call('flashsite/create-item', $input);
        $this->assertSame('publish', $published['item']['status']);
        $this->assertSame([], $published['incomplete']);
        $this->assertSame(500000.0, $GLOBALS['flashsite_test_post_meta'][$published['item']['id']]['fs_preco'], 'Campos gravados antes de publicar.');

        // Ficha incompleta nunca publica e não deixa lixo.
        $before = $this->postCount();
        $incomplete = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'Sem preço', 'status' => 'publish', 'fields' => ['referencia' => 'IMO-3']]);
        $this->assertSame('validation_failed', $incomplete->get_error_code());
        $this->assertTrue(isset($incomplete->get_error_data()['errors']['preco']));
        $this->assertSame($before, $this->postCount());

        // Rascunho incompleto é aceite e sinalizado (como no painel).
        $wip = $fx->call('flashsite/create-item', ['collection' => 'imovel', 'title' => 'Em curso', 'fields' => ['referencia' => 'IMO-4']]);
        $this->assertSame('draft', $wip['item']['status']);
        $this->assertTrue(isset($wip['incomplete']['preco']));
        $notYet = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $wip['item']['id'], 'status' => 'publish']);
        $this->assertSame('validation_failed', $notYet->get_error_code());
        $ok = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $wip['item']['id'], 'status' => 'publish', 'fields' => ['preco' => 99000]]);
        $this->assertSame('publish', $ok['item']['status']);

        // Um item publicado não pode ficar inválido.
        $break = $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $published['item']['id'], 'fields' => ['preco' => '']]);
        $this->assertSame('validation_failed', $break->get_error_code());
        $this->assertSame(500000.0, $GLOBALS['flashsite_test_post_meta'][$published['item']['id']]['fs_preco'], 'Nada foi gravado.');
        $this->assertSame('publish', $GLOBALS['flashsite_test_posts'][$published['item']['id']]->post_status);
    }

    private function requiredTaxonomyAndBools(): void
    {
        $fx = $this->fx;
        $draft = $fx->call('flashsite/create-item', ['collection' => 'x_ficha', 'title' => 'Ficha', 'fields' => ['ativo' => true]]);
        $this->assertTrue(is_array($draft), 'Preset externo x_* sem código específico.');
        $this->assertSame('required', $draft['incomplete']['codigo']['code']);
        $this->assertTrue(isset($draft['incomplete']['tax_categoria']), 'Taxonomia obrigatória sem termo por defeito.');
        $id = $draft['item']['id'];
        $blocked = $fx->call('flashsite/update-item', ['collection' => 'x_ficha', 'id' => $id, 'status' => 'publish']);
        $this->assertSame('validation_failed', $blocked->get_error_code());

        $fixed = $fx->call('flashsite/update-item', ['collection' => 'x_ficha', 'id' => $id, 'fields' => ['codigo' => 'C-1'], 'taxonomies' => ['categoria' => 'a'], 'status' => 'publish']);
        $this->assertSame('publish', $fixed['item']['status']);
        $this->assertSame('a', $fixed['item']['taxonomies']['categoria'][0]['slug']);
        $this->assertSame(['categoria'], $fixed['changed_taxonomies']);
        $next = $fx->call('flashsite/update-item', ['collection' => 'x_ficha', 'id' => $id, 'fields' => ['nivel' => 'alto']]);
        $this->assertTrue($next['item']['fields']['ativo'], 'Bool preservado em patch.');
        $this->assertSame('a', $next['item']['taxonomies']['categoria'][0]['slug']);
        $this->assertSame([], $next['changed_taxonomies']);
        $off = $fx->call('flashsite/update-item', ['collection' => 'x_ficha', 'id' => $id, 'fields' => ['ativo' => false]]);
        $this->assertFalse($off['item']['fields']['ativo']);
    }

    private function ownershipAndTrash(): void
    {
        $fx = $this->fx;
        $prato = $fx->call('flashsite/create-item', ['collection' => 'prato', 'title' => 'Bacalhau']);
        $pratoId = $prato['item']['id'];
        $imovelId = array_values(array_filter($GLOBALS['flashsite_test_posts'], static fn (WP_Post $p): bool => $p->post_type === 'fs_imovel'))[0]->ID;

        // O ID de outro tipo de conteúdo é rejeitado, igual a "não existe".
        foreach (['flashsite/get-item', 'flashsite/update-item', 'flashsite/trash-item'] as $name) {
            $this->assertSame('item_not_found', $fx->call($name, ['collection' => 'imovel', 'id' => $pratoId])->get_error_code(), $name);
        }
        $post = new WP_Post(5);
        $GLOBALS['flashsite_test_posts'][5] = $post;
        $post->post_type = 'page';
        $post->post_status = 'draft';
        $this->assertSame('item_not_found', $fx->call('flashsite/get-item', ['collection' => 'imovel', 'id' => 5])->get_error_code(), 'Não lê post types arbitrários.');
        $this->assertSame('item_not_found', $fx->call('flashsite/trash-item', ['collection' => 'imovel', 'id' => 5])->get_error_code());
        $this->assertSame('page', $post->post_type);
        $this->assertSame('draft', $post->post_status, 'A página não foi tocada.');
        unset($GLOBALS['flashsite_test_posts'][5]);

        // Permissões por item.
        $GLOBALS['flashsite_test_current_user_caps']['edit_post:' . $imovelId] = false;
        $this->assertSame('forbidden', $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $imovelId, 'title' => 'Hack'])->get_error_code());
        $this->assertSame('forbidden', $fx->call('flashsite/get-item', ['collection' => 'imovel', 'id' => $imovelId])->get_error_code());
        unset($GLOBALS['flashsite_test_current_user_caps']['edit_post:' . $imovelId]);
        $GLOBALS['flashsite_test_current_user_caps']['delete_post:' . $imovelId] = false;
        $before = $GLOBALS['flashsite_test_posts'][$imovelId]->post_status;
        $this->assertSame('forbidden', $fx->call('flashsite/trash-item', ['collection' => 'imovel', 'id' => $imovelId])->get_error_code());
        $this->assertSame($before, $GLOBALS['flashsite_test_posts'][$imovelId]->post_status);
        unset($GLOBALS['flashsite_test_current_user_caps']['delete_post:' . $imovelId]);

        // Lixo recuperável, idempotente, nunca definitivo.
        $trashed = $fx->call('flashsite/trash-item', ['collection' => 'imovel', 'id' => $imovelId]);
        $this->assertSame(['id' => $imovelId, 'collection' => 'imovel', 'status' => 'trash'], $trashed);
        $this->assertTrue(isset($GLOBALS['flashsite_test_posts'][$imovelId]), 'Continua no WordPress (Lixo).');
        $this->assertSame('trash', $fx->call('flashsite/trash-item', ['collection' => 'imovel', 'id' => $imovelId])['status']);
        $this->assertSame('item_in_trash', $fx->call('flashsite/update-item', ['collection' => 'imovel', 'id' => $imovelId, 'title' => 'x'])->get_error_code());
        $this->assertFalse(isset($this->fx->abilities['flashsite/delete-item']), 'Sem apagar definitivo.');
    }

    private function listing(): void
    {
        $fx = $this->fx;
        for ($i = 1; $i <= 3; $i++) {
            $fx->call('flashsite/create-item', ['collection' => 'prato', 'title' => "Prato $i"]);
        }
        $foreign = $fx->call('flashsite/create-item', ['collection' => 'prato', 'title' => 'Prato alheio']);
        $GLOBALS['flashsite_test_posts'][$foreign['item']['id']]->post_author = 2;

        $all = $fx->call('flashsite/list-items', ['collection' => 'prato']);
        $this->assertSame(5, $all['total']);
        $this->assertSame('prato', $all['items'][0]['collection']);
        $this->assertTrue(isset($all['items'][0]['id'], $all['items'][0]['title'], $all['items'][0]['status'], $all['items'][0]['modified'], $all['items'][0]['language']) || array_key_exists('language', $all['items'][0]));

        $big = $fx->call('flashsite/list-items', ['collection' => 'prato', 'per_page' => 500]);
        $this->assertSame(50, $GLOBALS['flashsite_test_last_query']['posts_per_page'], 'per_page limitado.');
        $this->assertSame(50, $big['per_page']);
        $paged = $fx->call('flashsite/list-items', ['collection' => 'prato', 'per_page' => 2, 'page' => 2]);
        $this->assertCount(2, $paged['items']);
        $this->assertSame(3, $paged['total_pages']);
        $search = $fx->call('flashsite/list-items', ['collection' => 'prato', 'search' => 'alheio']);
        $this->assertSame(1, $search['total']);
        $this->assertSame(0, $fx->call('flashsite/list-items', ['collection' => 'prato', 'status' => 'publish'])['total']);
        $this->assertSame('invalid_status', $fx->call('flashsite/list-items', ['collection' => 'prato', 'status' => 'nope'])->get_error_code());

        $sorted = $fx->call('flashsite/list-items', ['collection' => 'prato', 'orderby' => 'title', 'order' => 'DESC']);
        $this->assertTrue(is_array($sorted));
        $this->assertSame('invalid_orderby', $fx->call('flashsite/list-items', ['collection' => 'prato', 'orderby' => 'field:nada'])->get_error_code());
        $this->assertSame('invalid_when', $fx->call('flashsite/list-items', ['collection' => 'prato', 'when' => 'ontem'])->get_error_code());
        $this->assertSame('lang_unsupported', $fx->call('flashsite/list-items', ['collection' => 'prato', 'lang' => 'fr'])->get_error_code());

        // Sem editar itens de terceiros: só os próprios.
        $this->asManager([], ['edit_others_fs_prato_items']);
        $own = $fx->call('flashsite/list-items', ['collection' => 'prato']);
        $this->assertSame(4, $own['total'], 'Itens alheios ocultos sem edit_others.');
        $this->assertSame(1, $GLOBALS['flashsite_test_last_query']['author']);
        $this->asManager();
    }
}
