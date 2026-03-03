<?php

namespace Softspring\CmsTranslationPlugin\Admin\Menu;

use RuntimeException;
use Softspring\CmsBundle\Admin\Menu\MenuHelper;
use Softspring\CmsSectionsPlugin\Admin\Menu\AbstractSectionMenuProvider;
use Softspring\CmsSectionsPlugin\Model\SectionInterface;

class SectionMenuProvider extends AbstractSectionMenuProvider
{
    public static function getPriority(): int
    {
        return 253;
    }

    /**
     * @throws RuntimeException
     */
    public function getMenu(array $menu, ?string $currentSelection = null, ?object $entity = null): array
    {
        /** @var SectionInterface $section */
        $section = $entity;

        if (1 == count($section->getLocales())) {
            return $menu;
        }

        $index = MenuHelper::getMenuIndex('versions', $menu);

        return array_merge(array_slice($menu, 0, $index), [
            $this->getMenuItem('translations', $currentSelection, $section, 'PERMISSION_SFS_CMS_ADMIN_SECTION_TRANSLATIONS'),
        ], array_slice($menu, $index));
    }
}
