<?php

/**
 * Update Bundle List Command.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace AppBundle\Command;

use eZ\Publish\API\Repository\Values\Content\Query;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateBundlesListCommand extends ContainerAwareCommand
{
    /**
     * @param array $fields
     * @return string
     */
    private function calculateChecksum(array $fields = array()) {
        $string = '';
        foreach ($fields as $field) {
            $string += $field;
        }
        return md5($string);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:update_bundles_list');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $packagistServiceProvider = $this->getContainer()->get('app.packagist_service_provider');
        $userService = $repository->getUserService();
        $searchService = $repository->getSearchService();
        $contentService = $repository->getContentService();
        $permissionResolver = $repository->getPermissionResolver();

        $user = $userService->loadUserByLogin('admin');
        $permissionResolver->setCurrentUserReference($user);

        $query = new Query();
        $criterion = new Query\Criterion\ParentLocationId($this->getContainer()->getParameter('bundles.location_id'));
        $query->filter = $criterion;

        $result = $searchService->findContent($query);

        foreach ($result->searchHits as $searchHit) {
            $currentPackage = $searchHit->valueObject;
            $package = $packagistServiceProvider->getPackageDetails($currentPackage->getFieldValue('bundle_id'));
            $output->write($currentPackage->getFieldValue('bundle_id'));

            $packageChecksum = $this->calculateChecksum(array(
                'updated' => (int) $package['updated']->format('U'),
                'description' => $package['description'],
                'downloads' => $package['downloads'],
                'stars' => $package['stars'],
                'forks' => $package['forks'],
            ));

            if ($packageChecksum !== $currentPackage->getFieldValue('checksum')->__toString()) {
                $contentInfo = $contentService->loadContentInfo($searchHit->valueObject->versionInfo->contentInfo->id);
                $contentDraft = $contentService->createContentDraft($contentInfo);

                $contentUpdateStruct = $contentService->newContentUpdateStruct();
                $contentUpdateStruct->initialLanguageCode = 'eng-GB';
                $contentUpdateStruct->setField('updated', (int) $package['updated']->format('U'));
                $contentUpdateStruct->setField('downloads', $package['downloads']);
                $contentUpdateStruct->setField('stars', $package['stars']);
                $contentUpdateStruct->setField('forks', $package['forks']);
                $contentUpdateStruct->setField('checksum', $packageChecksum);

$xmlText = <<< EOX
<?xml version='1.0' encoding='utf-8'?>
<section 
    xmlns="http://docbook.org/ns/docbook" 
    xmlns:xlink="http://www.w3.org/1999/xlink" 
    xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" 
    xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" 
    version="5.0-variant ezpublish-1.0">
<para>{$package['description']}</para>
</section>
EOX;

                $contentUpdateStruct->setField('description', $xmlText);

                $contentDraft = $contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                $content = $contentService->publishVersion($contentDraft->versionInfo);

                $output->writeln(': Updated');
            }
            else {
                $output->writeln(': OK');
            }
        }
        $output->writeln("All bundles has been successfully updated.");
    }
}
