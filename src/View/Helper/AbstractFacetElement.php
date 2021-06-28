<?php declare(strict_types=1);

namespace Search\View\Helper;

use Laminas\Mvc\Application;
use Laminas\View\Helper\AbstractHelper;

class AbstractFacetElement extends AbstractHelper
{
    /**
     * @var string
     */
    protected $partial;

    /**
     * @var Application $application
     */
    protected $application;

    /**
     * @var \Omeka\View\Helper\Api
     */
    protected $api;

    /**
     * @var \Laminas\I18n\View\Helper\Translate}
     */
    protected $translate;

    /**
     * @var int
     */
    protected $siteId;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Create one facet as link, checkbox or button.
     *
     * @return string|array
     */
    public function __invoke(string $name, array $facet, bool $asData = false)
    {
        // Variables are static to speed up process.
        static $urlHelper;
        static $partialHelper;
        static $escapeHtml;
        static $translate;
        static $facetLabel;

        static $mvcEvent;
        static $routeMatch;
        static $request;

        static $route;
        static $params;
        static $queryBase;

        static $facetsData = [];

        if (is_null($mvcEvent)) {
            $plugins = $this->getView()->getHelperPluginManager();
            $urlHelper = $plugins->get('url');
            $partialHelper = $plugins->get('partial');
            $escapeHtml = $plugins->get('escapeHtml');
            $translate = $plugins->get('translate');
            $facetLabel = $plugins->get('facetLabel');

            $this->api = $plugins->get('api');
            $this->translate = $translate;

            $mvcEvent = $this->application->getMvcEvent();
            $routeMatch = $mvcEvent->getRouteMatch();
            $request = $mvcEvent->getRequest();

            $route = $routeMatch->getMatchedRouteName();
            $params = $routeMatch->getParams();
            $queryBase = $request->getQuery()->toArray();

            if ($plugins->get('status')->isSiteRequest()) {
                $this->siteId = $plugins
                    ->get('Laminas\View\Helper\ViewModel')
                    ->getRoot()
                    ->getVariable('site')
                    ->id();
            }

            unset($queryBase['page']);
        }

        $facetValue = (string) $facet['value'];
        if (!isset($facetsData[$name][$facetValue])) {
            $query = $queryBase;

            // The facet value is compared against a string (the query args).
            $facetValueLabel = $this->facetValueLabel($name, $facetValue);
            if (strlen($facetValueLabel)) {
                if (isset($query['limit'][$name]) && array_search($facetValue, $query['limit'][$name]) !== false) {
                    $values = $query['limit'][$name];
                    $values = array_filter($values, function ($v) use ($facetValue) {
                        return $v !== $facetValue;
                    });
                        $query['limit'][$name] = $values;
                        $active = true;
                } else {
                    $query['limit'][$name][] = $facetValue;
                    $active = false;
                }
                $url = $urlHelper($route, $params, ['query' => $query]);
            } else {
                $active = false;
                $url = '';
            }

            $facetsData[$name][$facetValue] = [
                'name' => $name,
                'value' => $facetValue,
                'label' => $facetValueLabel,
                'count' => $facet['count'],
                'active' => $active,
                'url' => $url,
                // To speed up process.
                'escapeHtml' => $escapeHtml,
                'translate' => $translate,
                'facetLabel' => $facetLabel,
            ];
        }

        if ($asData) {
            return $facetsData[$name][$facetValue];
        }

        return strlen($facetsData[$name][$facetValue]['label'])
            ? $partialHelper($this->partial, $facetsData[$name][$facetValue])
            : '';
    }

    protected function facetValueLabel(string $name, string $facetValue): ?string
    {
        if (!strlen($facetValue)) {
            return null;
        }

        // TODO Simplify the list of field names (for historical reasons).
        switch ($name) {
            case 'resource':
                $data = ['id' => $facetValue];
                // The site id is required in public.
                if ($this->siteId) {
                    $data['site_id'] = $this->siteId;
                }
                /** @var \Omeka\Api\Representation\ItemSetRepresentation $resource */
                $resource = $this->api->searchOne('resources', $data)->getContent();
                return $resource
                    ? (string) $resource->displayTitle()
                    // Manage the case where a resource was indexed but removed.
                    // In public side, the item set should belong to a site too.
                    : null;

            case 'item_sets':
            case 'itemSet':
            case 'item_set_id':
            case 'item_set_id_field':
                $data = ['id' => $facetValue];
                // The site id is required in public.
                if ($this->siteId) {
                    $data['site_id'] = $this->siteId;
                }
                /** @var \Omeka\Api\Representation\ItemSetRepresentation $resource */
                $resource = $this->api->searchOne('item_sets', $data)->getContent();
                return $resource
                    ? (string) $resource->displayTitle()
                    // Manage the case where a resource was indexed but removed.
                    // In public side, the item set should belong to a site too.
                    : null;

            case 'resource_classes':
            case 'resourceClass':
            case 'resource_class_id':
            case 'resource_class_id_field':
                $translate = $this->translate;
                if (is_numeric($facetValue)) {
                    try {
                        /** @var \Omeka\Api\Representation\ResourceClassRepresentation $resource */
                        $resource = $this->api->read('resource_classes', ['id' => $facetValue])->getContent();
                    } catch (\Exception $e) {
                        return null;
                    }
                    return $translate($resource->label());
                }
                $resource = $this->api->searchOne('resource_classes', ['term' => $facetValue])->getContent();
                return $resource
                    ? $translate($resource->label())
                    // Manage the case where a resource was indexed but removed.
                    : null;
                break;

            case 'resource_templates':
            case 'resourceTemplate':
            case 'resource_template_id':
            case 'resource_template_id_field':
                if (is_numeric($facetValue)) {
                    try {
                        /** @var \Omeka\Api\Representation\ResourceTemplateRepresentation $resource */
                        $resource = $this->api->read('resource_templates', ['id' => $facetValue])->getContent();
                    } catch (\Exception $e) {
                        return null;
                    }
                    return $resource->label();
                }
                $resource = $this->api->searchOne('resource_templates', ['label' => $facetValue])->getContent();
                return $resource
                    ? $resource->label()
                    // Manage the case where a resource was indexed but removed.
                    : null;
                break;

            case 'properties':
            default:
                return $facetValue;
        }
    }
}
