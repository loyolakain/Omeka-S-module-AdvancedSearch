<?php declare(strict_types=1);
namespace AdvancedSearch\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use AdvancedSearch\Form\Admin\SearchEngineConfigureForm;

class SearchEngineConfigureFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');
        $translator = $services->get('MvcTranslator');

        $form = new SearchEngineConfigureForm(null, $options);
        $form->setTranslator($translator);
        $form->setApiManager($api);

        return $form;
    }
}