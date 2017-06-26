<?php

/**
 * PackagistServiceProvider
 *
 * Provides method to call Packagist.org API.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace AppBundle\Service\Packagist;

use Packagist\Api\Client;
use Guzzle\Http\Exception\CurlException;

class PackagistServiceProvider implements PackagistServiceProviderInterface
{
    const GITHUB_AVATAR_BASE_URL = "https://avatars2.githubusercontent.com/";

    /**
     * @var \Packagist\Api\Client
     */
    private $packagistClient;

    /**
     * @var array
     */
    private $excludedMaintainers;

    public function __construct(
        Client $packagistClient,
        $excludedMaintainers
    ) {
        $this->packagistClient = $packagistClient;
        $this->excludedMaintainers = $excludedMaintainers;
    }

    /**
     * @param $packageName
     * @return array
     */
    public function getPackageDetails($packageName)
    {
        try {
            $packageName = trim($packageName);
            $packageDetails = $this->callApi($packageName);
            return $packageDetails;
        } catch (CurlException $curlException) {
            return [];
        }
    }

    /**
     * @param $packageName
     * @return array
     */
    private function callApi($packageName)
    {
        $externalData = $this->packagistClient->get($packageName);
        $packageDetails['maintainers'] = $this->excludeMaintainers($externalData->getMaintainers());
        $packageDetails['authorAvatarUrl'] = $this->getAuthorAvatarUrl($externalData->getRepository());
        $packageDetails['description'] = $externalData->getDescription();
        $packageDetails['downloads'] = $externalData->getDownloads()->getTotal();
        $packageDetails['forks'] = 0;
        $packageDetails['stars'] = 0;
        $versions = $externalData->getVersions();
        if (is_array($versions) && !empty($versions)) {
            if (isset($versions['dev-master'])) {
                $version = 'dev-master';
            } else {
                $version = key($versions);
            }
            $packageDetails['updated'] = \DateTime::createFromFormat(\DateTime::ISO8601, $versions[$version]->getTime());
            $packageDetails['author'] = $versions[$version]->getAuthors();
        }
        return $packageDetails;
    }

    /**
     * @param string $repositoryUrl
     * @return string
     */
    private function getAuthorAvatarUrl($repositoryUrl)
    {
        $parsedUrl = parse_url($repositoryUrl);
        $parts = explode('/', $parsedUrl['path']);
        return self::GITHUB_AVATAR_BASE_URL . $parts[1];
    }

    /**
     * Removes unwanted maintainers
     *
     * @param array $maintainers
     * @return mixed
     */
    private function excludeMaintainers(array $maintainers)
    {
        foreach ($maintainers as $key => $value) {
            if (in_array($value->getName(), $this->excludedMaintainers)) {
                unset($maintainers[$key]);
            }
        }
        return $maintainers;
    }
}
