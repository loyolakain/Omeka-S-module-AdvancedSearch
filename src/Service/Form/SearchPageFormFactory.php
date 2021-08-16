<?php declare(strict_types=1);

namespace AdvancedSearch\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use AdvancedSearch\Form\Admin\SearchPageForm;

class SearchPageFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return (new SearchPageForm(null, $options))
            ->setApiManager($services->get('Omeka\ApiManager'))
            ->setFormAdapterManager($services->get('Search\FormAdapterManager'));
    }
}
