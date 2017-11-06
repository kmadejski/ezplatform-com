<?php

namespace AppBundle\Service\Packagist\Test;

use AppBundle\Service\Packagist\Mapper;
use AppBundle\Service\Packagist\Package;
use Packagist\Api\Result\Package as ApiPackage;
use Packagist\Api\Result\Package\Author;
use Packagist\Api\Result\Package\Downloads;
use Packagist\Api\Result\Package\Maintainer;
use Packagist\Api\Result\Package\Version;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    /**
     * @var ApiPackage
     */
    protected $apiPackage;

    /**
     * @var Package
     */
    protected $package;

    /**
     * @var Mapper
     */
    protected $mapper;

    protected function setUp()
    {
        parent::__construct();
        $this->apiPackage = $this->createPackagistApiPackage();
        $this->package = $this->createPackage();
        $this->mapper = new Mapper([]);
    }

    public function testCreatePackageFromPackagistApiResult()
    {
        $this->assertEquals(
            $this->package,
            $this->mapper->createPackageFromPackagistApiResult($this->apiPackage)
        );
    }

    private function createPackagistApiPackage()
    {
        $maintainerData = [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'homepage' => 'example.com'
        ];
        $maintainer = new Maintainer();
        $maintainer->fromArray($maintainerData);

        $authorData = [
            'role' => 'author',
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'homepage' => 'example.com',
        ];
        $author = new Author();
        $author->fromArray($authorData);

        $versionData = [
            'name' => 'dev-master',
            'time' => '2017-11-03T19:51:03+00:00',
            'authors' => [
                $author
            ]
        ];
        $version = new Version();
        $version->fromArray($versionData);

        $downloadsData = [
            'total' => 222
        ];
        $downloads = new Downloads();
        $downloads->fromArray($downloadsData);

        $data = [
            'name' => 'test/package',
            'description' => 'Test description',
            'time' => '',
            'maintainers' => [
                $maintainer
            ],
            'versions' => [
                $version
            ],
            'type' => '',
            'repository' => 'http://github.com/test/package',
            'downloads' => $downloads,
            'favers' => '',
            'abandoned' => false,
            'suggesters' => 0,
            'dependents' => 0,
            'githubStars' => 12,
            'githubForks' => 3,
        ];

        $package = new ApiPackage();
        $package->fromArray($data);

        return $package;
    }

    private function createPackage() {

        $maintainerData = [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'homepage' => 'example.com',
        ];
        $maintainer = new Maintainer();
        $maintainer->fromArray($maintainerData);

        $authorData = [
            'role' => 'author',
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'homepage' => 'example.com',
        ];
        $author = new Author();
        $author->fromArray($authorData);

        $package = new Package();

        $package->packageId = 'test/package';
        $package->description = 'Test description';
        $package->downloads = 222;
        $package->maintainers = [$maintainer];
        $package->authorAvatarUrl = Mapper::GITHUB_AVATAR_BASE_URL . 'test';
        $package->forks = 3;
        $package->stars = 12;
        $package->author = $author;
        $package->updateDate = \DateTime::createFromFormat(\DateTime::ISO8601, '2017-11-03T19:51:03+00:00');
        $package->checksum = '51483fa78c050b1f68dd9718531bfda8';

        return $package;
    }
}
