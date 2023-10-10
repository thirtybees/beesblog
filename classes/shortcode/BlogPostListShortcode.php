<?php

namespace BeesBlogModule;

use PrestaShopException;
use Shortcodes\Context\ContextFactory;
use Shortcodes\Context\LinkContext;
use Shortcodes\Shortcode\Shortcode;
use Validate;

class BlogPostListShortcode implements Shortcode
{
    const TAG = 'blogpost';

    private ContextFactory $contextFactory;

    /**
     * @param ContextFactory $contextFactory
     */
    public function __construct(ContextFactory $contextFactory)
    {
        $this->contextFactory = $contextFactory;
    }


    /**
     * @return string
     */
    function getTag(): string
    {
        return static::TAG;
    }

    /**
     * @param string $content
     * @param array $parameters
     * @param LinkContext $linkContext
     *
     * @return string
     * @throws PrestaShopException
     */
    function process(string $content, array $parameters, LinkContext $linkContext): string
    {
        if (isset($parameters['id']) && ($id = (int)$parameters['id'])) {
            if (isset($parameters['language']) || isset($parameters['shop'])) {
                $linkContext = $this->contextFactory->getContext(
                    $parameters['shop'] ?? $linkContext->getShopId(),
                    $parameters['language'] ?? $linkContext->getLanguageId()
                );
            }
            $post = new BeesBlogPost($id, $linkContext->getLanguageId(), $linkContext->getShopId());
            if (Validate::isLoadedObject($post)) {
                return '<a href="' . htmlspecialchars($post->link) . '">' . $content . '</a>';
            }
        }
        return $content;
    }
}