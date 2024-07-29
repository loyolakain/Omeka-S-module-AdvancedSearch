<?php declare(strict_types=1);

/*
 * Copyright BibLibre, 2016-2017
 * Copyright Daniel Berthereau, 2018-2024
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace AdvancedSearch\Form\Admin;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Helper\EscapeHtml;
use Omeka\Form\Element as OmekaElement;

class SearchConfigConfigureForm extends Form
{
    /**
     * @var \Laminas\View\Helper\EscapeHtml
     */
    protected $escapeHtml;

    /**
     * @var \Laminas\Form\FormElementManager
     */
    protected $formElementManager;

    /**
     * @var array
     */
    protected $suggesters = [];

    /**
     * @var \Laminas\Mvc\I18n\Translator
     */
    protected $translator;

    /**
     * @var array
     */
    protected $thumbnailTypes = [];

    public function init(): void
    {
        /** @var \AdvancedSearch\Api\Representation\SearchConfigRepresentation $searchConfig */
        $searchConfig = $this->getOption('search_config');
        $engine = $searchConfig->engine();
        if (empty($engine)) {
            return;
        }

        // This is the settings for the search config, not the search form one.

        // TODO Simplify the form with js, storing the whole form one time via ini or json or just add a button import/export.
        // TODO See UserProfile and https://docs.laminas.dev/laminas-form/v3/form-creation/creation-via-factory/

        $this
            ->setAttribute('id', 'form-search-config-configure');

        // Settings for the search engine. Can be overwritten by a specific form.

        $this
            ->add([
                'name' => 'request',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Request', // @translate
                ],
            ])
            ->get('request')
            ->add([
                'name' => 'default_results',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Default results to display when landing on search page', // @translate
                    'value_options' => [
                        'none' => 'Nothing', // @translate
                        'query' => 'Results of the query below', // @translate
                        'default' => 'Default results of the search engine', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'default_results',
                    'required' => false,
                    'value' => 'default',
                ],
            ])
            // TODO Use UrlQuery instead of Text for the default query to avoid conversion each time.
            ->add([
                'name' => 'default_query',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Default query', // @translate
                    'info' => 'The format of the query depends on the search form and the search engine.', // @translated
                ],
                'attributes' => [
                    'id' => 'default_query',
                ],
            ])
            ->add([
                'name' => 'default_query_post',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Complementary default query', // @translate
                    'info' => 'Mainly used to specify a default sort when request is empty, but other args are possible (default pagination, selected facets…).', // @translated
                ],
                'attributes' => [
                    'id' => 'default_query_post',
                ],
            ])
            ->add([
                'name' => 'hidden_query_filters',
                'type' => CommonElement\UrlQuery::class,
                'options' => [
                    'label' => 'Hidden query filter to limit results', // @translate
                    'info' => 'These args are appended to all queries. The format of the query depends on the search form and the search engine.', // @translated
                ],
                'attributes' => [
                    'id' => 'hidden_query_filters',
                ],
            ])
            ->add([
                'name' => 'fulltext_search',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Add a button to search record or full text (for content not stored in a property)', // @translate
                    'value_options' => [
                        '' => 'None', // @translate
                        'fulltext_checkbox' => 'Check box "Search full text"', // @translate
                        'record_checkbox' => 'Check box "Record only"', // @translate
                        'fulltext_radio' => 'Radio "Full text" and "Record only"', // @translate
                        'record_radio' => 'Radio "Record only" and "Full text"', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'fulltext_search',
                    'value' => '',
                ],
            ])
            ->add([
                'name' => 'validate_form',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Validate user query (useless in most of the cases)', // @translate
                ],
                'attributes' => [
                    'id' => 'validate_form',
                ],
            ])
        ;

        $this
            ->add([
                'name' => 'autosuggest',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Search field', // @translate
                ],
            ])
            ->get('autosuggest')
            ->add([
                'name' => 'suggester',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'label' => 'Suggester', // @translate
                    'value_options' => $this->suggesters,
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'autosuggest_suggester',
                    'multiple' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => ' ',
                ],
            ])
            ->add([
                'name' => 'url',
                'type' => CommonElement\OptionalUrl::class,
                'options' => [
                    'label' => 'Direct endpoint', // @translate
                    // @see https://solr.apache.org/guide/suggester.html#suggest-request-handler-parameters
                    'info' => 'This url allows to use an external endpoint to manage keywords and is generally quicker. Needed params should be appended.', // @translate
                ],
                'attributes' => [
                    'id' => 'autosuggest_url',
                ],
            ])
            ->add([
                'name' => 'url_param_name',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Optional query param name for direct endpoint', // @translate
                    'info' => 'For a direct Solr endpoint, it should be "suggest.q", else "q" is used by default.', // @translate
                ],
                'attributes' => [
                    'id' => 'autosuggest_url_param_name',
                ],
            ])
            ->add([
                'name' => 'fill_input',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Stay on form when selecting a suggestion (no auto-submit)', // @translate
                ],
                'attributes' => [
                    'id' => 'fill_input',
                ],
            ])
        ;

        // Settings for the form querier (advanced form and filters).

        /** @var \AdvancedSearch\Form\Admin\SearchConfigFilterFieldset $filterFieldset */
        $filterFieldset = $this->formElementManager->get(SearchConfigFilterFieldset::class, [
            'search_config' => $this->getOption('search_config'),
        ]);

        $this
            ->add([
                'name' => 'form',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Filters', // @translate
                ],
            ])
            ->get('form')

            ->add([
                'name' => 'button_submit',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Add a button "submit"', // @translate
                ],
                'attributes' => [
                    'id' => 'button_submit',
                ],
            ])
            ->add([
                'name' => 'label_submit',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label for submit', // @translate
                ],
                'attributes' => [
                    'id' => 'label_submit',
                    'required' => false,
                    'value' => 'Search', // @translate
                    'placeholder' => 'Search', // @translate
                ],
            ])
            ->add([
                'name' => 'button_reset',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Add a button "reset"', // @translate
                ],
                'attributes' => [
                    'id' => 'button_reset',
                ],
            ])
            ->add([
                'name' => 'label_reset',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label for reset', // @translate
                ],
                'attributes' => [
                    'id' => 'label_reset',
                    'required' => false,
                    'value' => 'Reset fields', // @translate
                    'placeholder' => 'Reset fields', // @translate
                ],
            ])
            ->add([
                'name' => 'attribute_form',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Add attribute "form" to input elements', // @translate
                ],
                'attributes' => [
                    'id' => 'attribute_form',
                ],
            ])
            ->add([
                'name' => 'filters',
                'type' => Element\Collection::class,
                'options' => [
                    'label' => 'Filters', // @ŧranslate
                    'info' => 'List of filters that will be displayed in the search form, formatted as ini. The section is a unique name. Main keys are: field, label and type.', // @translate
                    'count' => 0,
                    'allow_add' => true,
                    'allow_remove' => true,
                    'should_create_template' => true,
                    'template_placeholder' => '__index__',
                    'create_new_objects' => true,
                    'target_element' => $filterFieldset,
                ],
                'attributes' => [
                    'id' => 'form_filters',
                    'required' => false,
                    'class' => 'form-fieldset-collection',
                    'data-label-index' => $this->translator->translate('Filter {index}'), // @ŧranslate
                ],
            ])
            ->add([
                'name' => 'plus',
                'type' => Element\Button::class,
                'options' => [
                    'label' => ' ',
                    'label_options' => [
                        'disable_html_escape' => true,
                    ],
                    'label_attributes' => [
                        'class' => 'search-fieldset-action-label',
                    ],
                ],
                'attributes' => [
                    // Don't use o-icon-add.
                    'class' => 'search-fieldset-action search-fieldset-plus fa fa-plus add-value button',
                    'aria-label' => 'Add a filter', // @translate
                ],
            ])

            // Advanced is a sub-fieldset of form.
            ->add([
                'name' => 'advanced',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Advanced  filters', // @translate
                ],
            ])
            ->get('advanced')
            ->add([
                'name' => 'default_number',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Number of advanced filters to display', // @translate
                    'info' => 'The filters may be managed via js for a better display.', // @translate
                ],
                'attributes' => [
                    'id' => 'default_number',
                    'required' => false,
                    'value' => '1',
                    'min' => '0',
                    // A mysql query supports 61 arguments maximum.
                    'max' => '49',
                    'step' => '1',
                ],
            ])
            ->add([
                'name' => 'max_number',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Maximum number of advanced filters to display', // @translate
                ],
                'attributes' => [
                    'id' => 'max_number',
                    'required' => false,
                    'value' => '10',
                    'min' => '0',
                    // A mysql query supports 61 arguments maximum.
                    'max' => '49',
                    'step' => '1',
                ],
            ])
            ->add([
                'name' => 'field_joiner',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Add the joiner ("and" or "or") to the advanced filters', // @translate
                ],
                'attributes' => [
                    'id' => 'field_joiner',
                ],
            ])
            ->add([
                'name' => 'field_joiner_not',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Add the joiner "not" to the advanced filters', // @translate
                ],
                'attributes' => [
                    'id' => 'field_joiner_not',
                ],
            ])
            ->add([
                'name' => 'field_operator',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Add the operator ("equal", "in", etc.) to the advanced filters', // @translate
                ],
                'attributes' => [
                    'id' => 'field_operator',
                ],
            ])
            ->add([
                'name' => 'field_operators',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'List of operators', // @translate
                    'info' => 'The default list is the full list available in advanced standard search form. Negative operators are removed when the joiner "not" is used.', // @translate
                    'as_key_value' => true,
                    'key_value_separator' => '=',
                ],
                'attributes' => [
                    'id' => 'field_operators',
                    'rows' => 12,
                    // This placeholder does not contain all query types.
                    'placeholder' => <<<'STRING'
                        eq = is exactly
                        in = contains
                        sw = starts with
                        ew = ends with
                        STRING, // @translate
                ],
            ])
            ->add([
                'name' => 'fields',
                'type' => CommonElement\DataTextarea::class,
                'options' => [
                    'label' => 'Fields', // @translate
                    'info' => 'List of filters that will be displayed in the search form. Format is "term = Label". The field should exist in all resources fields. Only properties are managed for internal search engine.', // @translate
                    'as_key_value' => true,
                    'key_value_separator' => '=',
                    'data_options' => [
                        'value' => null,
                        'label' => null,
                    ],
                ],
                'attributes' => [
                    'id' => 'fields',
                    // field (term) = label (order means weight).
                    'placeholder' => 'dcterms:title = Title',
                    'rows' => 12,
                ],
            ])
        ;

        // Settings for the results.

        /** @var \AdvancedSearch\Form\Admin\SearchConfigSortFieldset $sortFieldset */
        $sortFieldset = $this->formElementManager->get(SearchConfigSortFieldset::class, [
            'search_config' => $this->getOption('search_config'),
        ]);

        $this
            ->add([
                'name' => 'display',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Display', // @translate
                ],
            ])
            ->get('display')
            ->add([
                'name' => 'by_resource_type',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Separate resources by type (item set, items, etc.)', // @translate
                ],
                'attributes' => [
                    'id' => 'by_resource_type',
                ],
            ])
            ->add([
                'name' => 'template',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Template', // @translate
                    'info' => 'The template to use in your theme. Default is search/search.', // @translate
                ],
                'attributes' => [
                    'id' => 'template',
                ],
            ])
            ->add([
                'name' => 'breadcrumbs',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Breadcrumbs', // @translate
                ],
                'attributes' => [
                    'id' => 'breadcrumbs',
                ],
            ])
            ->add([
                'name' => 'search_filters',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'List of query filters', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'search_filters',
                    'value' => 'header',
                ],
            ])
            ->add([
                'name' => 'active_facets',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'List of active facets', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'active_facets',
                    'value' => 'none',
                ],
            ])
            ->add([
                'name' => 'total_results',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Total results', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'total_results',
                    'value' => 'header',
                ],
            ])
            ->add([
                'name' => 'search_form_simple',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Search form simple', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'search_form_simple',
                    'value' => 'none',
                ],
            ])
            ->add([
                'name' => 'search_form_quick',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Search form quick', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'search_form_quick',
                    'value' => 'none',
                ],
            ])
            ->add([
                'name' => 'paginator',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Paginator', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'paginator',
                    'value' => 'header',
                ],
            ])
            ->add([
                'name' => 'per_page',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Pagination per page', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'per_page',
                    'value' => 'header',
                ],
            ])
            ->add([
                'name' => 'sort',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Sort', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'sort',
                    'value' => 'header',
                ],
            ])
            ->add([
                'name' => 'grid_list',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Grid / list', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'header' => 'Results header', // @translate
                        'footer' => 'Results footer', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'grid_list',
                    'value' => 'header',
                ],
            ])
            ->add([
                'name' => 'grid_list_mode',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Grid / list default mode', // @translate
                    'value_options' => [
                        'auto' => 'Auto (previous user choice)', // @translate
                        'grid' => 'Grid', // @translate
                        'list' => 'List', // @translate
                        'grid_only' => 'Only grid', // @translate
                        'list_only' => 'Only list', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'grid_list_mode',
                    'value' => 'auto',
                ],
            ])
            ->add([
                'name' => 'thumbnail_mode',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Resource thumbnail', // @translate
                    'value_options' => [
                        'default' => 'Default resource thumbnail', // @translate
                        'none' => 'Never', // @translate
                        'all' => 'Always', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'thumbnail_mode',
                    'value' => 'default',
                ],
            ])
            ->add([
                'name' => 'thumbnail_type',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Thumbnail type', // @translate
                    'value_options' => array_combine($this->thumbnailTypes, $this->thumbnailTypes),
                ],
                'attributes' => [
                    'id' => 'thumbnail_type',
                    'value' => 'medium',
                ],
            ])
            ->add([
                'name' => 'allow_html',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Allow html in result values', // @translate
                ],
                'attributes' => [
                    'id' => 'allow_html',
                ],
            ])
            ->add([
                'name' => 'facets',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Block of facets', // @translate
                    'value_options' => [
                        'none' => 'No', // @translate
                        'before' => 'Before results', // @translate
                        'after' => 'After results', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'facets',
                    'value' => 'before',
                ],
            ])

            // TODO Add the style of pagination (prev/next or list of pages).

            ->add([
                'name' => 'per_page_list',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Labels for results per page', // @translate
                    'as_key_value' => true,
                    'key_value_separator' => '=',
                ],
                'attributes' => [
                    'id' => 'per_page_list',
                    'placeholder' => <<<'STRING'
                        10 = Results by 10
                        25 = Results by 25
                        50 = Results by 50
                        100 = Results by 100
                        STRING,
                    'rows' => 6,
                ],
            ])

            // field (term + asc/desc) = label (+ asc/desc) (order means weight).
            ->add([
                'name' => 'label_sort',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Sort label', // @translate
                ],
                'attributes' => [
                    'id' => 'label_sort',
                    'value' => 'Sort',
                ],
            ])

            ->add([
                'type' => Element\Collection::class,
                'name' => 'sort_list',
                'options' => [
                    'label' => 'Sort selector', // @ŧranslate
                    'info' => 'List of sort field that will be displayed in the results.', // @translate
                    'count' => 0,
                    'allow_add' => true,
                    'allow_remove' => true,
                    'should_create_template' => true,
                    'template_placeholder' => '__index__',
                    'create_new_objects' => true,
                    'target_element' => $sortFieldset,
                ],
                'attributes' => [
                    'id' => 'sort_list',
                    'required' => false,
                    'class' => 'form-fieldset-collection',
                    'data-label-index' => $this->translator->translate('Sort {index}'), // @ŧranslate
                ],
            ])
            ->add([
                'name' => 'plus',
                'type' => Element\Button::class,
                'options' => [
                    'label' => ' ',
                    'label_options' => [
                        'disable_html_escape' => true,
                    ],
                    'label_attributes' => [
                        'class' => 'search-fieldset-action-label',
                    ],
                ],
                'attributes' => [
                    // Don't use o-icon-add.
                    'class' => 'search-fieldset-action search-fieldset-plus fa fa-plus add-value button',
                    'aria-label' => 'Add a sort option', // @translate
                ],
            ])
        ;

        // Settings for the results (facets).
        // TODO Add the count or not.

        $this
            ->add([
                'name' => 'facet',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Facets', // @translate
                ],
            ])
            ->get('facet')
            ->add([
                'name' => 'label',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label above the list of facets', // @translate
                ],
                'attributes' => [
                    'id' => 'facet_label_facets',
                    'value' => 'Facets',
                ],
            ])
            ->add([
                'name' => 'label_no_facets',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label "No facets"', // @translate
                ],
                'attributes' => [
                    'id' => 'facet_label_no_facets',
                    'value' => 'No facets', // @translate
                ],
            ])
            ->add([
                'name' => 'mode',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Facet mode', // @translate
                    'value_options' => [
                        'button' => 'Send request with a button', // @translate
                        'link' => 'Send request directly', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'facet_mode',
                    'required' => false,
                    'value' => 'button',
                ],
            ])
            ->add([
                'name' => 'list',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'List of facets', // @translate
                    'infos' => 'With the internal search engine, the option "all facets" may be slow when there are facets and filters for item sets or sites.', // @translate
                    'value_options' => [
                        'available' => 'Available facets only', // @translate
                        'all' => 'All facets, even with 0 results (see info)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'facet_list',
                    'required' => false,
                    'value' => 'available',
                ],
            ])
            ->add([
                'name' => 'display_active',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Display the list of active facets', // @translate
                ],
                'attributes' => [
                    'id' => 'facet_display_active',
                    'required' => false,
                    'value' => true,
                ],
            ])
            ->add([
                'name' => 'label_active_facets',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label "Active facets"', // @translate
                ],
                'attributes' => [
                    'id' => 'facet_label_active_facets',
                    'value' => 'Active facets', // @translate
                ],
            ])
            ->add([
                'name' => 'display_submit',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Position of the button "Apply filters"', // @translate
                    'value_options' => [
                        'none' => 'None', // @translate
                        'above' => 'Above facets', // @translate
                        'below' => 'Below facets', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'facet_display_submit',
                    'required' => false,
                    'value' => 'above',
                ],
            ])
            ->add([
                'name' => 'label_submit',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label for submit', // @translate
                ],
                'attributes' => [
                    'id' => 'facet_label_submit',
                    'required' => false,
                    'value' => 'Apply facets', // @translate
                    'placeholder' => 'Apply facets', // @translate
                ],
            ])
            ->add([
                'name' => 'display_reset',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Position of the button "Reset facets"', // @translate
                    'value_options' => [
                        'none' => 'None', // @translate
                        'above' => 'Above facets', // @translate
                        'below' => 'Below facets', // @translate
                        'both' => 'Both', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'facet_display_reset',
                    'required' => false,
                    'value' => 'above',
                ],
            ])
            ->add([
                'name' => 'label_reset',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label for reset', // @translate
                ],
                'attributes' => [
                    'id' => 'facet_label_reset',
                    'required' => false,
                    'value' => 'Reset facets', // @translate
                    'placeholder' => 'Reset facets', // @translate
                ],
            ])
            ->add([
                'name' => 'facets',
                'type' => CommonElement\IniTextarea::class,
                'options' => [
                    'label' => 'Facets', // @translate
                    'info' => 'List of facets that will be displayed in the search page, formatted as ini. The section is a unique name. Keys are: field, label, type, order, limit, state, more, languages, data_types, main_types, values, display_count, and specific options, like thesaurus, min and max.', // @translate
                    // TODO Convert documentation into help. See application/view/common/form-row.phtml
                    'documentation' => nl2br(<<<'MARKDOWN'
                        #"></a><div class="field-description">
                        - Input types may be Checkbox (default), RangeDouble, Select, SelectRange, Thesaurus, Tree.
                        - For "RangeDouble" and "SelectRange", the minimum and maximum should be set as "min" and "max", and "step" too.
                        - With type "Thesaurus", the option "thesaurus" should be set with the id. It requires the module Thesaurus.
                        - "Tree" can be used for item sets when module ItemSetsTree is enabled and data indexed recursively.
                        - "languages" allows to filter values by language. It is a single or list of language codes. To get the values without language too, use "null" or append "|" to the string.
                        - "data_types" allows to filter values by some specific data types, for example a custom vocab.
                        - "main_types" allows to filter values by main data type ("literal", "uri" or "resource").
                        - "values" allows to filter values by a list of values.
                        - "order" may be "alphabetic" (asc, default), "alphabetic desc", "total" (desc), "total asc", "values" (asc), "values desc".
                        - "state" defines the state of a facet and may be "static" (default, that is open), "expand" or "collapse".
                        </div><a style="display: none" href="#
                        MARKDOWN), // @translate
                    'ini_typed_mode' => true,
                ],
                'attributes' => [
                    'id' => 'facet_facets',
                    'rows' => 12,
                    'placeholder' => '',
                ],
            ])
            ->add([
                'name' => 'available_facets',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Available facets', // @translate
                    'info' => 'List of all available facets, among which some can be copied above.', // @translate
                    'as_key_value' => true,
                    'key_value_separator' => '=',
                ],
                'attributes' => [
                    'id' => 'facet_available_facets',
                    'value' => $this->getAvailableFacetFields(),
                    'rows' => 12,
                ],
            ])
        ;

        $this
            ->addFormFieldset()
            ->prepareInputFilters();
    }

    protected function prepareInputFilters(): Form
    {
        // Input filters should be added after elements.
        $inputFilter = $this->getInputFilter();

        // A check is done because the specific form may remove them.
        if ($inputFilter->has('autosuggest')) {
            $inputFilter
                ->get('autosuggest')
                ->add([
                    'name' => 'limit',
                    'required' => false,
                ])
            ;
        }

        if ($inputFilter->has('form')) {
            $inputFilter
                ->get('form')
                ->add([
                    'name' => 'default_number',
                    'required' => false,
                ])
                ->add([
                    'name' => 'max_number',
                    'required' => false,
                ])
            ;
        }

        return $this;
    }

    protected function addFormFieldset(): self
    {
        /** @var \AdvancedSearch\Api\Representation\SearchConfigRepresentation $searchConfig */
        $searchConfig = $this->getOption('search_config');

        $formAdapter = $searchConfig->formAdapter();
        if (!$formAdapter) {
            return $this;
        }

        $configFormClass = $formAdapter->getConfigFormClass();
        if (!$configFormClass) {
            return $this;
        }

        /** @var \Laminas\Form\Fieldset $fieldset */
        $fieldset = $this->formElementManager
            ->get($configFormClass, ['search_config' => $searchConfig]);

        if (method_exists($fieldset, 'skipDefaultElementsOrFieldsets')) {
            foreach ($fieldset->skipDefaultElementsOrFieldsets() as $skip) {
                $this->remove($skip);
            }
        }

        $this->add($fieldset);

        return $this;
    }

    protected function getAvailableFacetFields(): array
    {
        /** @var \AdvancedSearch\Api\Representation\SearchConfigRepresentation $searchConfig */
        $searchConfig = $this->getOption('search_config');
        $searchEngine = $searchConfig->engine();
        $searchAdapter = $searchEngine->adapter();
        if (empty($searchAdapter)) {
            return [];
        }

        $options = [];
        $fields = $searchAdapter->setSearchEngine($searchEngine)->getAvailableFacetFields();
        foreach ($fields as $name => $field) {
            $options[$name] = $field['label'] ?? $name;
        }
        return $options;
    }

    public function setEscapeHtml(EscapeHtml $escapeHtml): self
    {
        $this->escapeHtml = $escapeHtml;
        return $this;
    }

    public function setFormElementManager($formElementManager): self
    {
        $this->formElementManager = $formElementManager;
        return $this;
    }

    public function setSuggesters(array $suggesters): self
    {
        $this->suggesters = $suggesters;
        return $this;
    }

    public function setThumbnailTypes(array $thumbnailTypes): self
    {
        $this->thumbnailTypes = $thumbnailTypes;
        return $this;
    }

    public function setTranslator(Translator $translator): self
    {
        $this->translator = $translator;
        return $this;
    }
}
