[![License](https://img.shields.io/packagist/l/synolia/sylius-akeneo-plugin.svg)](https://github.com/synolia/SyliusAkeneoPlugin/blob/master/LICENSE)
![Tests](https://github.com/synolia/SyliusAkeneoPlugin/workflows/CI/badge.svg?branch=master)
[![Version](https://img.shields.io/packagist/v/synolia/sylius-akeneo-plugin.svg)](https://packagist.org/packages/synolia/sylius-akeneo-plugin)
[![Total Downloads](https://poser.pugx.org/synolia/sylius-akeneo-plugin/downloads)](https://packagist.org/packages/synolia/sylius-akeneo-plugin)

<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Sylius Akeneo Plugin</h1>
<p align="center">
    <a href="https://plugins.sylius.com/plugin/akeneo-plugin/"  target="_blank">
        <img src="https://sylius.com/assets/badge-approved-by-sylius.png" width="100px" />
    </a>
</p>
<p align="center">This plugin allow you to import data from <a href="https://www.akeneo.com/" target="_blank">Akeneo PIM</a>.</p>

## Features

* Configure your Akeneo Account - [Documentation](docs/CONFIGURE.md)
* Configure which data should be imported and how it will be imported. - [Documentation](docs/CONFIGURE_DETAIL.md)
* Customize imports according to your business needs. - [Documentation](docs/CUSTOMIZE.md)
* Launch imports through Cli or Back-Office - [Documentation](docs/LAUNCH.md)

## Requirements

| | Version          |
| :--- |:-----------------|
| PHP  | ^8.0       |
| Sylius | ^1.10             |
| Akeneo PIM  | ^4.0, ^5.0, ^6.0 |


## Installation

1. Add the bundle and dependencies in your composer.json :

    ```shell
    composer require synolia/sylius-akeneo-plugin --no-scripts
    ```
   
2. Enable the plugin in your `config/bundles.php` file by add
   
    ```php
    Synolia\SyliusAkeneoPlugin\SynoliaSyliusAkeneoPlugin::class => ['all' => true],
    ```
   
3. Import required config in your `config/packages/_sylius.yaml` file:
    
    ```yaml
    imports:
        - { resource: "@SynoliaSyliusAkeneoPlugin/Resources/config/config.yaml" }
    ```
   
4. Import routing in your `config/routes.yaml` file:

    ```yaml
    synolia_akeneo:
        resource: "@SynoliaSyliusAkeneoPlugin/Resources/config/routes.yaml"
        prefix: '/%sylius_admin.path_name%'
    ```

5. Add Asset trait to Product.php and ProductVariant.php entities

   ```php
   <?php

   declare(strict_types=1);

   namespace App\Entity\Product;
   
   use Doctrine\ORM\Mapping as ORM;
   use Synolia\SyliusAkeneoPlugin\Entity\ProductAssetTrait;

   /**
    * @ORM\Entity
    * @ORM\Table(name="sylius_product")
    */
    class Product
    {
        ...
        use ProductAssetTrait {
            __construct as private initializeAssetsCollection;
        }
   
        public function __construct()
        {
            parent::__construct();

            $this->initializeAssetsCollection();
        }
        ...
    }
   ```

   ```php
   <?php

   declare(strict_types=1);

   namespace App\Entity\Product;
   
   use Doctrine\ORM\Mapping as ORM;
   use Synolia\SyliusAkeneoPlugin\Entity\ProductVariantAssetTrait;

   /**
    * @ORM\Entity
    * @ORM\Table(name="sylius_product_variant")
    */
    class ProductVariant
    {
        ...
        use ProductVariantAssetTrait {
            __construct as private initializeAssetsCollection;
        }
   
        public function __construct()
        {
            parent::__construct();

            $this->initializeAssetsCollection();
        }
        ...
    }
   ```

6. Apply plugin migrations to your database:

    ```shell
    bin/console doctrine:migrations:migrate
    ```
   
7. Clear cache

    ```shell
    bin/console cache:clear
    ```

## Development

* See [How to contribute](CONTRIBUTING.md)
* See [How to customize your import using processors](docs/customize/PROCESSORS.md)

## Akeneo Enterprise Edition

### Reference Entity and Asset attribute types

* [Everything you need to know about Reference Entity in Sylius](docs/reference_entity/REFERENCE_ENTITY.md)
* [Everything you need to know about Asset in Sylius](docs/asset/ASSET.md)

## License

This library is under the [EUPL-1.2 license](LICENSE).

## Credits

Developed by [Synolia](https://synolia.com/).
