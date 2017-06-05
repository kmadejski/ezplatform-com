<?php

namespace AppBundle\Command;

use eZ\Publish\API\Repository\Values\Content\Query;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateBundlesListCommand extends ContainerAwareCommand
{
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
            $package = $packagistServiceProvider->getPackageDetails($currentPackage->getFieldValue('bundle_id')->__toString());

            $output->write($currentPackage->getFieldValue('bundle_id')->__toString());
            /**
             * Checking if package needs to be updated
             */
            if ($this->getChecksum($package['updated']->format('l d F Y'), $package['downloads'])
                !== $this->getChecksum($currentPackage->getFieldValue('updated')->__toString(),
                    $currentPackage->getFieldValue('downloads')->__toString())
            ) {
                /**
                 * Content update
                 */
                $contentInfo = $contentService->loadContentInfo($searchHit->valueObject->versionInfo->contentInfo->id);
                $contentDraft = $contentService->createContentDraft($contentInfo);

                $contentUpdateStruct = $contentService->newContentUpdateStruct();
                $contentUpdateStruct->initialLanguageCode = 'eng-GB';
                $contentUpdateStruct->setField('updated', (int) $package['updated']->format('U'));
                $contentUpdateStruct->setField('downloads', $package['downloads']);
                $contentUpdateStruct->setField('stars', $package['stars']);
                $contentUpdateStruct->setField('forks', $package['forks']);

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

    private function getChecksum($updated, $downloads)
    {
        return md5($updated . $downloads);
    }
}
