<?php

namespace BeesBlogModule;

use BeesBlog;
use Configuration;
use Db;
use DbQuery;
use Link;
use PrestaShopException;
use Shop;
use Shortcodes\Context\ContextFactory;
use Shortcodes\Convertor\ConvertChange;
use Shortcodes\Convertor\ConvertField;
use Shortcodes\Convertor\ConvertResult;
use Shortcodes\Convertor\HtmlToShortcodeConvertor;
use Shortcodes\Entity\EntityTypeBase;
use Shortcodes\Link\LinkService;
use Translate;
use Validate;

/**
 * @implements EntityTypeBase<BeesBlogPost>
 */
class BlogPostEntityType extends EntityTypeBase
{


    /**
     * @return string
     */
    public function getId(): string
    {
        return 'beesblog:blogpost';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->l('Blog Post');
    }

    /**
     * @param int $entityId
     * @param int $languageId
     *
     * @return string|null
     * @throws PrestaShopException
     */
    public function getEntityName(int $entityId, int $languageId)
    {
        $entity = $this->getEntity($entityId);
        if ($entity) {
            return $this->getLangValue($entity->title, $languageId);
        }
        return null;
    }

    /**
     * @param Link $link
     * @param int $entityId
     *
     * @return string|null
     * @throws PrestaShopException
     */
    public function getBackofficeUrl(Link $link, int $entityId): ?string
    {
        return $link->getAdminLink('AdminBeesBlogPost', true, [
            'id_bees_blog_post' => $entityId,
            'updatebees_blog_post' => 1
        ]);
    }

    public function getFields(): array
    {
        return [
            'content' => $this->l('Blog Post Content')
        ];
    }

    /**
     * @return int[]
     *
     * @throws PrestaShopException
     */
    public function getEntitiesToConvert(): array
    {
        $selectQuery = (new DbQuery())
            ->select('DISTINCT bl.id_bees_blog_post')
            ->from('bees_blog_post_lang', 'bl')
            ->innerJoin('bees_blog_post', 'b', '(b.id_bees_blog_post = bl.id_bees_blog_post)')
            ->innerJoin('lang', 'l', '(l.id_lang = bl.id_lang AND l.active)')
            ->where('b.active')
            ->where('bl.lang_active');

        $conds = [];
        foreach ($this->getFields() as $column => $ignore) {
            $conds[] = 'bl.'.bqSQL($column).' LIKE "%href%"';
        }
        $selectQuery->where(implode(" OR ", $conds));
        return array_column(Db::getInstance()->getArray($selectQuery), 'id_bees_blog_post');
    }

    /**
     * @param HtmlToShortcodeConvertor $convertor
     * @param int $entityId
     *
     * @return ConvertResult
     * @throws PrestaShopException
     */
    public function convertHtmlToShortcodes(HtmlToShortcodeConvertor $convertor, int $entityId): ConvertResult
    {
        $result = new ConvertResult();

        $entityId = (int)$entityId;
        $sql =(new DbQuery())
            ->select('id_lang')
            ->from('bees_blog_post_lang')
            ->where('id_bees_blog_post = ' . $entityId);
        foreach ($this->getFields() as $column => $ignore) {
            $sql->select($column);
        }

        $conn = Db::getInstance();
        $rows = $conn->getArray($sql);

        $shopId = (int)Configuration::get('PS_SHOP_DEFAULT');
        foreach ($rows as $row) {
            $langId = (int)$row['id_lang'];
            $values = [];
            foreach ($row as $key => $old) {
                if ($key != 'id_lang') {
                    $old = (string)$old;
                    $field = new ConvertField($this, $entityId, $shopId, $langId, $key);
                    $new = $convertor->convert($old, $field);
                    if ($old !== $new) {
                        $values[$key] = pSQL($new, true);
                        $result->addChange(new ConvertChange($field, $old, $new));
                    }
                }
            }
            if ($values) {
                $conn->update('bees_blog_post_lang', $values, "id_bees_blog_post = $entityId AND id_lang = $langId");
            }
        }
        return $result;
    }

    /**
     * @param ContextFactory $contextFactory
     * @param LinkService $linkService
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getAllUrls(ContextFactory $contextFactory, LinkService $linkService): array
    {
        $conn = Db::getInstance();
        $blogposts = $conn->getArray((new DbQuery())
            ->select('DISTINCT bl.id_bees_blog_post, bl.id_lang, bl.link_rewrite')
            ->from('bees_blog_post_lang', 'bl')
            ->innerJoin('bees_blog_post', 'b', '(b.id_bees_blog_post = bl.id_bees_blog_post)')
            ->innerJoin('lang', 'l', '(l.id_lang = bl.id_lang AND l.active)')
            ->where('b.active')
            ->where('bl.lang_active')
            ->orderBy('bl.id_bees_blog_post, bl.id_lang')
        );

        $mapping = [];
        $shops = Shop::getShops(true, null, true);

        foreach ($blogposts as $row) {
            foreach ($shops as $shopId) {
                $langId = (int)$row['id_lang'];
                $blogPostId = (int)$row['id_bees_blog_post'];
                $linkRewrite = (string)$row['link_rewrite'];
                $url = BeesBlog::getBeesBlogLink('beesblog_post', ['blog_rewrite' => $linkRewrite], (int)$shopId, $langId);
                $mapping[$url] = $blogPostId;
            }
        }

        return $mapping;
    }

    /**
     * @return string
     */
    public function getFrontController()
    {
        return 'post';
    }

    /**
     * @return string
     */
    public function getLinkShortcode()
    {
        return BlogPostLinkShortcode::TAG;
    }

    /**
     * @param int $entityId
     *
     * @return BeesBlogPost|null
     * @throws PrestaShopException
     */
    protected function loadEntity(int $entityId)
    {
        $blogpost = new BeesBlogPost($entityId);
        if (Validate::isLoadedObject($blogpost)) {
            return $blogpost;
        }
        return null;
    }

    /**
     * @param string $input
     *
     * @return string
     */
    public function l($input)
    {
        return Translate::getModuleTranslation('faqsnippets', $input, 'faqsnippets');
    }
}