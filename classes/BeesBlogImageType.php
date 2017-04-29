<?php
/**
 * 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace BeesBlogModule;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogImageType
 */
class BeesBlogImageType extends \ImageType
{
    /**
     * Generate images
     */
    public static function generateBlogImage()
    {
        $getBlogImage = BeesBlogPost::getBlogImage();
        $getCategoryImage = BeesBlogCategory::getCatImage();
        $categoryTypes = static::getImagesTypes('blog_category');
        $postTypes = BeesBlogImageType::getImagesTypes('blog_post');

        foreach ($categoryTypes as $imageType) {
            foreach ($getCategoryImage as $categoryImage) {
                $path = _PS_IMG_DIR_.\BeesBlog::CATEGORY_IMG_DIR.$categoryImage['id_bees_blog_category'].'.jpg';
                \ImageManager::resize(
                    $path,
                    _PS_IMG_DIR_.\BeesBlog::CATEGORY_IMG_DIR.$categoryImage['id_bees_blog_category'].'-'.stripslashes($imageType['type_name']).'.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                );
            }
        }
        foreach ($postTypes as $imageType) {
            foreach ($getBlogImage as $blogImage) {
                $path = _PS_IMG_DIR_.\BeesBlog::POST_IMG_DIR.$blogImage['id_bees_blog_post'].'.jpg';
                \ImageManager::resize(
                    $path,
                    _PS_IMG_DIR_.\BeesBlog::POST_IMG_DIR.$blogImage['id_bees_blog_post'].'-'.stripslashes($imageType['type_name']).'.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                );
            }
        }
    }

    /**
     * Delete images
     */
    public static function deleteBlogImage()
    {
        $getBlogImage = BeesBlogPost::getBlogImage();
        $getCategoryImage = BeesBlogCategory::getCatImage();
        $categoryTypes = BeesBlogImageType::getImagesTypes('beesblog_category');
        $postTypes = BeesBlogImageType::getImagesTypes('beesblog_post');
        foreach ($categoryTypes as $imageType) {
            foreach ($getCategoryImage as $categoryImage) {
                $dir = _PS_IMG_DIR_.\BeesBlog::CATEGORY_IMG_DIR.$categoryImage['id_bees_blog_category'].'-'.stripslashes($imageType['type_name']).'.jpg';
                if (file_exists($dir)) {
                    unlink($dir);
                }
            }
        }
        foreach ($postTypes as $imageType) {
            foreach ($getBlogImage as $blogImage) {
                $dir = _PS_IMG_DIR_.\BeesBlog::POST_IMG_DIR.$blogImage['id_bees_blog_post'].'-'.stripslashes($imageType['type_name']).'.jpg';
                if (file_exists($dir)) {
                    unlink($dir);
                }
            }
        }
    }
}
