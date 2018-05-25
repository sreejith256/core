<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Tables\Renderer;

use Gibbon\Tables\Column;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Forms\FormFactory;
use Gibbon\Tables\Renderer\RendererInterface;

/**
 * PaginatedRenderer
 *
 * @version v16
 * @since   v16
 */
class PaginatedRenderer extends SimpleRenderer implements RendererInterface
{
    protected $path;
    protected $criteria;
    protected $factory;
    
    /**
     * Creates a renderer that uses page info from the QueryCriteria to display a paginated data table.
     * Hooks into the DataTable functionality in core.js to load using AJAX.
     *
     * @param QueryCriteria $criteria
     * @param string $path
     */
    public function __construct(QueryCriteria $criteria, $path)
    {
        $this->path = $path;
        $this->criteria = $criteria;
        $this->factory = FormFactory::create();
    }

    /**
     * Render the table to HTML. TODO: replace with Twig.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    public function renderTable(DataTable $table, DataSet $dataSet)
    {
        $output = '';

        $output .= '<div class="linkTop">';
        foreach ($table->getHeader() as $header) {
            $output .= $header->getOutput();
        }
        $output .= '</div>';

        $output .= '<div id="'.$table->getID().'">';
        $output .= '<div class="dataTable">';

        $filterOptions = $table->getMetaData('filterOptions', []);

        $output .= '<header>';
            $output .= '<div>';
            $output .= $this->renderPageCount($dataSet);
            $output .= $this->renderPageFilters($dataSet, $filterOptions);
            $output .= '</div>';
            $output .= $this->renderFilterOptions($dataSet, $filterOptions);
            $output .= $this->renderPageSize($dataSet);
            $output .= $this->renderPagination($dataSet);
        $output .= '</header>';

        $output .= parent::renderTable($table, $dataSet);

        if ($dataSet->getPageCount() > 1) {
            $output .= '<footer>';
            $output .= $this->renderPageCount($dataSet);
            $output .= $this->renderPagination($dataSet);
            $output .= '</footer>';
        }

        $output .= '</div></div><br/>';

        // Initialize the jQuery Data Table functionality
        $output .="
        <script>
        $(function(){
            $('#".$table->getID()."').gibbonDataTable('.".str_replace(' ', '%20', $this->path)."', ".$this->criteria->toJson().", ".$dataSet->getResultCount().");
        });
        </script>";

        return $output;
    }

    /**
     * Overrides the SimpleRenderer header to add sortable column classes & data attribute.
     * @param Column $column
     * @return Element
     */
    protected function createTableHeader(Column $column)
    {
        $th = parent::createTableHeader($column);

        if ($sortBy = $column->getSortable()) {
            $th->addClass('sortable');
            $th->addData('sort', implode(',', $sortBy));

            foreach ($sortBy as $sortColumn) {
                if ($this->criteria->hasSort($sortColumn)) {
                    $th->addClass('sorting sort'.$this->criteria->getSortBy($sortColumn));
                }
            }
        }

        return $th;
    }

    /**
     * Render the record count for this page, and total record count.
     *
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderPageCount(DataSet $dataSet)
    {
        $output = '<small style="margin-right: 10px;">';

        $output .= $this->criteria->hasSearchText()? __('Search').' ' : '';
        $output .= $dataSet->isSubset()? __('Results') : __('Records');
        $output .= $dataSet->count() > 0? ' '.$dataSet->getPageFrom().'-'.$dataSet->getPageTo().' '.__('of').' ' : ': ';
        $output .= $dataSet->isSubset()? $dataSet->getResultCount() : $dataSet->getTotalCount();

        $output .= '</small>';

        return $output;
    }

    /**
     * Render the currently active filters for this data set.
     *
     * @param DataSet $dataSet
     * @param array $filters
     * @return string
     */
    protected function renderPageFilters(DataSet $dataSet, array $filters)
    {
        $output = '<small>';

        if ($this->criteria->hasFilter()) {
            $output .= __('Filtered by').' ';

            $criteriaUsed = array();
            foreach ($this->criteria->getFilterBy() as $name => $value) {
                $key = $name.':'.$value;
                $criteriaUsed[$name] = isset($filters[$key]) ? $filters[$key] : __(ucfirst($name)).': '.__(ucfirst($value));
            }

            foreach ($criteriaUsed as $name => $label) {
                $output .= '<input type="button" class="filter" value="'.$label.'" data-filter="'.$name.'"> ';
            }

            $output .= '<input type="button" class="filter clear buttonLink" value="'.__('Clear').'">';
        }

        $output .= '</small>';

        return $output;
    }

    /**
     * Render the available options for filtering the data set.
     *
     * @param DataSet $dataSet
     * @param array $filters
     * @return string
     */
    protected function renderFilterOptions(DataSet $dataSet, array $filters)
    {
        if (empty($filters)) return '';
        
        return $this->factory->createSelect('filter')
            ->fromArray($filters)
            ->setClass('filters floatNone')
            ->placeholder(__('Filters'))
            ->getOutput();
    }

    /**
     * Render the page size drop-down. Hidden if there's less than one page of total results.
     *
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderPageSize(DataSet $dataSet)
    {
        if ($dataSet->getPageSize() <= 0 || $dataSet->getPageCount() <= 1) return '';

        return $this->factory->createSelect('limit')
            ->fromArray(array(10, 25, 50, 100))
            ->setClass('limit floatNone')
            ->selected($dataSet->getPageSize())
            ->append('<small style="line-height: 30px;margin-left:5px;">'.__('Per Page').'</small>')
            ->getOutput();
    }

    /**
     * Render the set of numeric page buttons for naigating paginated data sets.
     *
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderPagination(DataSet $dataSet)
    {
        if ($dataSet->getPageCount() <= 1) return '';

        $pageNumber = $dataSet->getPage();

        $output = '<div class="floatRight">';
            $output .= '<input type="button" class="paginate" data-page="'.$dataSet->getPrevPageNumber().'" '.($dataSet->isFirstPage()? 'disabled' : '').' value="'.__('Prev').'">';

            foreach ($dataSet->getPaginatedRange() as $page) {
                if ($page === '...') {
                    $output .= '<input type="button" disabled value="...">';
                } else {
                    $class = ($page == $pageNumber)? 'active paginate' : 'paginate';
                    $output .= '<input type="button" class="'.$class.'" data-page="'.$page.'" value="'.$page.'">';
                }
            }

            $output .= '<input type="button" class="paginate" data-page="'.$dataSet->getNextPageNumber().'" '.($dataSet->isLastPage()? 'disabled' : '').' value="'.__('Next').'">';
        $output .= '</div>';

        return $output;
    }
}