<?php

namespace Softspring\CmsTranslationPlugin\Admin\Menu;

use Softspring\CmsBundle\Admin\Menu\AbstractContentMenuProvider;
use Softspring\CmsBundle\Admin\Menu\MenuHelper;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;

class ContentMenuProvider extends AbstractContentMenuProvider
{
    public static function getPriority(): int
    {
        return 253;
    }

    /**
     * @throws InvalidContentException
     * @throws \RuntimeException
     */
    public function getMenu(array $menu, ?string $currentSelection = null, array $context = []): array
    {
        [$content, $contentType, $contentConfig] = $this->getContent($context);

        if (1 == sizeof($content->getLocales())) {
            return $menu;
        }

        $index = MenuHelper::getMenuIndex('versions', $menu);

        return array_merge(array_slice($menu, 0, $index), [
            $this->getMenuItem('translations', $currentSelection, $content, $contentType, $contentConfig, 'version_translations'),
        ], array_slice($menu, $index));
    }
}
