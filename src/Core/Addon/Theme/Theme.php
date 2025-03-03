<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PrestaShop\PrestaShop\Core\Addon\Theme;

use AbstractAssetManager;
use Configuration;
use PrestaShop\PrestaShop\Core\Addon\AddonInterface;
use PrestaShop\PrestaShop\Core\Util\ArrayFinder;
use PrestaShop\PrestaShop\Core\Util\File\YamlParser;

class Theme implements AddonInterface
{
    /**
     * @var ArrayFinder
     */
    private $attributes;

    /**
     * @param array $attributes Theme attributes
     * @param string|null $configurationCacheDirectory Default _PS_CACHE_DIR_
     * @param string $themesDirectory Default _PS_ALL_THEMES_DIR_
     */
    public function __construct(
        array $attributes,
        ?string $configurationCacheDirectory = null,
        string $themesDirectory = _PS_ALL_THEMES_DIR_
    ) {
        if (isset($attributes['parent'])) {
            if (null === $configurationCacheDirectory) {
                $configurationCacheDirectory = (new Configuration())->get('_PS_CACHE_DIR_');
            }

            $yamlParser = new YamlParser($configurationCacheDirectory);
            $parentAttributes = $yamlParser->parse($themesDirectory . '/' . $attributes['parent'] . '/config/theme.yml');
            $parentAttributes['preview'] = 'themes/' . $attributes['parent'] . '/preview.png';
            $parentAttributes['parent_directory'] = rtrim($attributes['directory'], '/') . '/';
            $attributes = array_merge($parentAttributes, $attributes);
        }

        $attributes['directory'] = rtrim($attributes['directory'], '/') . '/';

        if (file_exists($themesDirectory . $attributes['name'] . '/preview.png')) {
            $attributes['preview'] = 'themes/' . $attributes['name'] . '/preview.png';
        }

        $this->attributes = new ArrayFinder($attributes);
    }

    public function get($attr = null, $default = null)
    {
        return $this->attributes->get($attr, $default);
    }

    public function has($attr)
    {
        return $this->attributes->offsetExists($attr);
    }

    public function getName()
    {
        return $this->attributes->get('name');
    }

    public function getDirectory()
    {
        return $this->attributes->get('directory');
    }

    public function getModulesToEnable()
    {
        $modulesToEnable = $this->get('global_settings.modules.to_enable', []);
        $modulesToHook = $this->get('global_settings.hooks.modules_to_hook', []);

        foreach ($modulesToHook as $hookName => $modules) {
            if (is_array($modules)) {
                foreach (array_values($modules) as $module) {
                    if (is_array($module)) {
                        $module = key($module);
                    }
                    if (null !== $module && !in_array($module, $modulesToEnable)) {
                        $modulesToEnable[] = $module;
                    }
                }
            }
        }

        return $modulesToEnable;
    }

    public function getModulesToDisable()
    {
        return $this->get('dependencies.modules', []);
    }

    public function getPageSpecificAssets($pageId)
    {
        return [
            'css' => $this->getPageSpecificCss($pageId),
            'js' => $this->getPageSpecificJs($pageId),
        ];
    }

    public function onInstall()
    {
        return true;
    }

    public function onUninstall()
    {
        return true;
    }

    /**
     * Execute up files. You can update configuration, update sql schema.
     * No file modification.
     *
     * @return bool true for success
     */
    public function onUpgrade($version)
    {
        return true;
    }

    /**
     * Called when switching the current theme of the selected shop.
     * You can update configuration, enable/disable modules...
     *
     * @return bool true for success
     */
    public function onEnable()
    {
        return true;
    }

    /**
     * Not necessarily the opposite of enable. Use this method if
     * something must be done when switching to another theme (like uninstall
     * very specific modules for example).
     *
     * @return bool true for success
     */
    public function onDisable()
    {
        return true;
    }

    public function onReset()
    {
        return true;
    }

    public function setPageLayouts(array $layouts)
    {
        $this->attributes->set('theme_settings.layouts', $layouts);
    }

    public function getDefaultLayout()
    {
        $availableLayouts = $this->getAvailableLayouts();
        $defaultLayoutIdentifier = $this->attributes->get('theme_settings.default_layout');
        $defaultLayout = $availableLayouts[$defaultLayoutIdentifier];

        $defaultLayout['key'] = $defaultLayoutIdentifier;

        return $defaultLayout;
    }

    public function getPageLayouts()
    {
        return $this->attributes->get('theme_settings.layouts');
    }

    public function getAvailableLayouts()
    {
        return $this->attributes->get('meta.available_layouts');
    }

    public function getLayoutNameForPage($page)
    {
        $layout_name = $this->get('theme_settings.default_layout');
        if (isset($this->attributes['theme_settings']['layouts'][$page])
            && $this->attributes['theme_settings']['layouts'][$page]) {
            $layout_name = $this->attributes['theme_settings']['layouts'][$page];
        }

        return $layout_name;
    }

    public function getLayoutRelativePathForPage($page)
    {
        return 'layouts/' . $this->getLayoutNameForPage($page) . '.tpl';
    }

    private function getPageSpecificCss($pageId)
    {
        $css = array_merge(
            (array) $this->get('assets.css.all'),
            (array) $this->get('assets.css.' . $pageId)
        );
        foreach ($css as $key => &$entry) {
            // Required parameters
            if (!isset($entry['id']) || !isset($entry['path'])) {
                unset($css[$key]);

                continue;
            }
            if (!isset($entry['media'])) {
                $entry['media'] = AbstractAssetManager::DEFAULT_MEDIA;
            }
            if (!isset($entry['priority'])) {
                $entry['priority'] = AbstractAssetManager::DEFAULT_PRIORITY;
            }
            if (!isset($entry['inline'])) {
                $entry['inline'] = false;
            }
        }

        return $css;
    }

    private function getPageSpecificJs($pageId)
    {
        $js = array_merge(
            (array) $this->get('assets.js.all'),
            (array) $this->get('assets.js.' . $pageId)
        );
        foreach ($js as $key => &$entry) {
            // Required parameters
            if (!isset($entry['id']) || !isset($entry['path'])) {
                unset($js[$key]);

                continue;
            }
            if (!isset($entry['position'])) {
                $entry['position'] = AbstractAssetManager::DEFAULT_JS_POSITION;
            }
            if (!isset($entry['priority'])) {
                $entry['priority'] = AbstractAssetManager::DEFAULT_PRIORITY;
            }
            if (!isset($entry['inline'])) {
                $entry['inline'] = false;
            }
            if (!isset($entry['attribute'])) {
                $entry['attribute'] = false;
            }
        }

        return $js;
    }
}
