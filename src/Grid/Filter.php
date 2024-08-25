<?php

namespace Encore\Admin\Grid;

use Encore\Admin\Grid as GridClass;
use Encore\Admin\Grid\Filter\AbstractFilter;
use Encore\Admin\Grid\Filter\Between;
use Encore\Admin\Grid\Filter\Date;
use Encore\Admin\Grid\Filter\Day;
use Encore\Admin\Grid\Filter\EndsWith;
use Encore\Admin\Grid\Filter\Equal;
use Encore\Admin\Grid\Filter\Group;
use Encore\Admin\Grid\Filter\Gt;
use Encore\Admin\Grid\Filter\Hidden;
use Encore\Admin\Grid\Filter\Ilike;
use Encore\Admin\Grid\Filter\In;
use Encore\Admin\Grid\Filter\Layout\Layout;
use Encore\Admin\Grid\Filter\Like;
use Encore\Admin\Grid\Filter\Lt;
use Encore\Admin\Grid\Filter\Month;
use Encore\Admin\Grid\Filter\NotEqual;
use Encore\Admin\Grid\Filter\NotIn;
use Encore\Admin\Grid\Filter\Scope;
use Encore\Admin\Grid\Filter\StartsWith;
use Encore\Admin\Grid\Filter\Where;
use Encore\Admin\Grid\Filter\Year;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Class Filter.
 *
 * @method AbstractFilter     equal($column, $label = '')
 * @method AbstractFilter     notEqual($column, $label = '')
 * @method AbstractFilter     leftLike($column, $label = '')
 * @method AbstractFilter     like($column, $label = '')
 * @method AbstractFilter     contains($column, $label = '')
 * @method AbstractFilter     startsWith($column, $label = '')
 * @method AbstractFilter     endsWith($column, $label = '')
 * @method AbstractFilter     ilike($column, $label = '')
 * @method AbstractFilter     gt($column, $label = '')
 * @method AbstractFilter     lt($column, $label = '')
 * @method AbstractFilter     between($column, $label = '')
 * @method AbstractFilter     in($column, $label = '')
 * @method AbstractFilter     notIn($column, $label = '')
 * @method AbstractFilter     where($callback, $label = '', $column = null)
 * @method AbstractFilter     date($column, $label = '')
 * @method AbstractFilter     day($column, $label = '')
 * @method AbstractFilter     month($column, $label = '')
 * @method AbstractFilter     year($column, $label = '')
 * @method AbstractFilter     hidden($name, $value)
 * @method AbstractFilter     group($column, $label = '', $builder = null)
 */
class Filter implements Renderable
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var array<AbstractFilter>
     */
    protected $filters = [];

    /**
     * @var array<string, mixed>
     */
    protected static $supports = [];

    /**
     * If use id filter.
     *
     * @var bool
     */
    protected $useIdFilter = true;

    /**
     * Id filter was removed.
     *
     * @var bool
     */
    protected $idFilterRemoved = false;

    /**
     * Action of search form.
     *
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $view = 'admin::filter.container';

    /**
     * @var string
     */
    protected $filterID = 'filter-box';

    /**
     * @var string|null
     */
    protected $filterAjax;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var bool
     */
    public $expand = false;

    /**
     * @var Collection<int|string, mixed>
     */
    protected $scopes;

    /**
     * @var Layout
     */
    protected $layout;

    /**
     * Set this filter only in the layout.
     *
     * @var bool
     */
    protected $thisFilterLayoutOnly = false;

    /**
     * Columns of filter that are layout-only.
     *
     * @var array<mixed>
     */
    protected $layoutOnlyFilterColumns = [];

    /**
     * Primary key of giving model.
     *
     * @var mixed
     */
    protected $primaryKey;

    /**
     * Create a new filter instance.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        $this->primaryKey = $this->model->eloquent()->getKeyName();

        $this->initLayout();

        $this->equal($this->primaryKey, strtoupper($this->primaryKey));
        $this->scopes = new Collection();
    }

    /**
     * Initialize filter layout.
     *
     * @return void
     */
    protected function initLayout()
    {
        $this->layout = new Filter\Layout\Layout($this);
    }

    /**
     * Set action of search form.
     *
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get grid model.
     *
     * @return Model
     */
    public function getModel()
    {
        $conditions = array_merge(
            $this->conditions(),
            $this->scopeConditions()
        );

        return $this->model->addConditions($conditions);
    }

    /**
     * Get grid model pure.
     *
     * @return Model
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * Set ID of search form.
     *
     * @param string $filterID
     *
     * @return $this
     */
    public function setFilterID($filterID)
    {
        $this->filterID = $filterID;

        return $this;
    }

    /**
     * Get filter ID.
     *
     * @return string
     */
    public function getFilterID()
    {
        return $this->filterID;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        $this->setFilterID("{$this->name}-{$this->filterID}");

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get filter Ajax url.
     *
     * @return string
     */
    public function getFilterAjax(){
        return $this->filterAjax;
    }

    /**
     * Set filter Ajax url.
     *
     * @param string $filterAjax
     * @return $this
     */
    public function setFilterAjax($filterAjax){
        $this->filterAjax = $filterAjax;

        return $this;
    }

    /**
     * Disable Id filter.
     *
     * @return $this
     */
    public function disableIdFilter(bool $disable = true)
    {
        $this->useIdFilter = !$disable;

        return $this;
    }

    /**
     * Remove ID filter if needed.
     *
     * @return void
     */
    public function removeIDFilterIfNeeded()
    {
        if (!$this->useIdFilter && !$this->idFilterRemoved) {
            $this->removeDefaultIDFilter();

            $this->layout->removeDefaultIDFilter();

            $this->idFilterRemoved = true;
        }
    }

    /**
     * Remove the default ID filter.
     *
     * @return void
     */
    protected function removeDefaultIDFilter()
    {
        array_shift($this->filters);
    }

    /**
     * Remove filter by filter id.
     *
     * @param mixed $id
     *
     * @return void
     */
    protected function removeFilterByID($id)
    {
        $this->filters = array_filter($this->filters, function (AbstractFilter $filter) use ($id) {
            return $filter->getId() != $id;
        });
    }

    /**
     * Get all conditions of the filters.
     *
     * @return array<mixed>
     */
    public function conditions()
    {
        $inputs = Arr::dot(Request::all());

        $inputs = array_filter($inputs, function ($input) {
            return $input !== '' && !is_null($input);
        });

        $this->sanitizeInputs($inputs);

        if (empty($inputs)) {
            return [];
        }

        $params = [];

        foreach ($inputs as $key => $value) {
            Arr::set($params, $key, $value);
        }

        $conditions = [];

        $this->removeIDFilterIfNeeded();

        foreach ($this->filters() as $filter) {
            if (in_array($column = $filter->getColumn(), $this->layoutOnlyFilterColumns)) {
                $filter->default(Arr::get($params, $column));
            } else {
                $conditions[] = $filter->getCondition($params);
            }
        }

        return tap(array_filter($conditions), function ($conditions) {
            if (!empty($conditions)) {
                $this->expand();
            }
        });
    }

    /**
     * @param array<mixed> $inputs
     *
     * @return array<mixed>|void
     */
    protected function sanitizeInputs(&$inputs)
    {
        if (!$this->name) {
            return $inputs;
        }

        $inputs = collect($inputs)->filter(function ($input, $key) {
            return Str::startsWith($key, "{$this->name}_");
        })->mapWithKeys(function ($val, $key) {
            $key = str_replace("{$this->name}_", '', $key);

            return [$key => $val];
        })->toArray();
    }

    /**
     * Set this filter layout only.
     *
     * @return $this
     */
    public function layoutOnly()
    {
        $this->thisFilterLayoutOnly = true;

        return $this;
    }

    /**
     * Add a filter to grid.
     *
     * @param AbstractFilter $filter
     *
     * @return AbstractFilter
     */
    protected function addFilter(AbstractFilter $filter)
    {
        $this->layout->addFilter($filter);

        $filter->setParent($this);

        if ($this->thisFilterLayoutOnly) {
            $this->thisFilterLayoutOnly = false;
            $this->layoutOnlyFilterColumns[] = $filter->getColumn();
        }

        return $this->filters[] = $filter;
    }

    /**
     * Use a custom filter.
     *
     * @param AbstractFilter $filter
     *
     * @return AbstractFilter
     */
    public function use(AbstractFilter $filter)
    {
        return $this->addFilter($filter);
    }

    /**
     * Get all filters.
     *
     * @return AbstractFilter[]
     */
    public function filters()
    {
        return $this->filters;
    }

    /**
     * @param string $key
     * @param string $label
     *
     * @return mixed
     */
    public function scope($key, $label = '')
    {
        return tap(new Scope($key, $label), function (Scope $scope) {
            return $this->scopes->push($scope);
        });
    }

    /**
     * Get all filter scopes.
     *
     * @return Collection<int|string, mixed>
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Get current scope.
     *
     * @return Scope|null
     */
    public function getCurrentScope()
    {
        $key = request(Scope::QUERY_NAME);

        return $this->scopes->first(function ($scope) use ($key) {
            return $scope->key == $key;
        });
    }

    /**
     * Get scope conditions.
     *
     * @return array<mixed>
     */
    protected function scopeConditions()
    {
        if ($scope = $this->getCurrentScope()) {
            return $scope->condition();
        }

        return [];
    }

    /**
     * Add a new layout column.
     *
     * @param int      $width
     * @param \Closure $closure
     *
     * @return $this
     */
    public function column($width, \Closure $closure)
    {
        $width = $width <= 1 ? round(12 * $width) : $width;

        $this->layout->column($width, $closure);

        return $this;
    }

    /**
     * Expand filter container.
     *
     * @return $this
     */
    public function expand()
    {
        $this->expand = true;

        return $this;
    }

    /**
     * Execute the filter with conditions.
     *
     * @param bool $toArray
     *
     * @return array<mixed>|Collection<int|string, mixed>|mixed
     */
    public function execute($toArray = true)
    {
        if (method_exists($this->model->eloquent(), 'paginate')) {
            $this->model->usePaginate(true);

            return $this->model->buildData($toArray);
        }
        $conditions = array_merge(
            $this->conditions(),
            $this->scopeConditions()
        );

        return $this->model->addConditions($conditions)->buildData($toArray);
    }

    /**
     * @param callable $callback
     * @param int      $count
     *
     * @return bool
     */
    public function chunk(callable $callback, $count = 100)
    {
        $conditions = array_merge(
            $this->conditions(),
            $this->scopeConditions()
        );

        return $this->model->addConditions($conditions)->chunk($callback, $count);
    }

    /**
     * Get the string contents of the filter view.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $this->removeIDFilterIfNeeded();

        if (!isset($this->filterAjax) && empty($this->filters)) {
            return '';
        }

        return view($this->view)->with([
            'action'   => $this->action ?: $this->urlWithoutFilters(),
            'layout'   => $this->layout,
            'filterID' => $this->filterID,
            'expand'   => $this->expand,
        ])->render();
    }

    /**
     * Get url without filter queryString.
     *
     * @return string
     */
    public function urlWithoutFilters()
    {
        /** @var Collection<int|string, mixed> $columns */
        $columns = collect($this->filters)->map->getColumn()->flatten();

        $isnull_columns = $columns->map(function ($value) {
            return 'isnull-' . $value;
        });

        $pageKey = 'page';

        if ($gridName = $this->model->getGrid()->getName()) {
            $pageKey = "{$gridName}_{$pageKey}";
        }

        $columns->push($pageKey);

        $groupNames = collect($this->filters)->filter(function ($filter) {
            return $filter instanceof Group;
        })->map(function (AbstractFilter $filter) {
            return "{$filter->getId()}_group";
        });

        return $this->fullUrlWithoutQuery(
            $columns->merge($groupNames)->merge($isnull_columns)
        );
    }

    /**
     * Get url without scope queryString.
     *
     * @return string
     */
    public function urlWithoutScopes()
    {
        return $this->fullUrlWithoutQuery(Scope::QUERY_NAME);
    }

    /**
     * Get full url without query strings.
     *
     * @param Arrayable<int|string, mixed>|array<mixed>|string $keys
     *
     * @return string
     */
    protected function fullUrlWithoutQuery($keys)
    {
        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $keys = (array) $keys;

        $request = request();

        $query = $request->query();
        Arr::forget($query, $keys);

        // remove quick search
        Arr::forget($query, GridClass::getSearchKey());

        $question = $request->getBaseUrl().$request->getPathInfo() == '/' ? '/?' : '?';

        return count($request->query()) > 0
            ? $request->url().$question.http_build_query($query)
            : $request->fullUrl();
    }

    /**
     * @param string $name
     * @param string $filterClass
     *
     * @return void
     */
    public static function extend($name, $filterClass)
    {
        if (!is_subclass_of($filterClass, AbstractFilter::class)) {
            throw new \InvalidArgumentException("The class [$filterClass] must be a type of ".AbstractFilter::class.'.');
        }

        static::$supports[$name] = $filterClass;
    }

    /**
     * @param string $abstract
     * @param array<mixed> $arguments
     * @return mixed|void
     */
    public function resolveFilter($abstract, $arguments)
    {
        if (isset(static::$supports[$abstract])) {
            return new static::$supports[$abstract](...$arguments);
        }
    }

    /**
     * Register builtin filters
     *
     * @return void
     */
    public static function registerFilters()
    {
        $filters = [
            'equal'      => Equal::class,
            'notEqual'   => NotEqual::class,
            'ilike'      => Ilike::class,
            'like'       => Like::class,
            'gt'         => Gt::class,
            'lt'         => Lt::class,
            'between'    => Between::class,
            'group'      => Group::class,
            'where'      => Where::class,
            'in'         => In::class,
            'notIn'      => NotIn::class,
            'date'       => Date::class,
            'day'        => Day::class,
            'month'      => Month::class,
            'year'       => Year::class,
            'hidden'     => Hidden::class,
            'contains'   => Like::class,
            'startsWith' => StartsWith::class,
            'endsWith'   => EndsWith::class,
        ];

        foreach ($filters as $name => $filterClass) {
            static::extend($name, $filterClass);
        }
    }

    /**
     * Generate a filter object and add to grid.
     *
     * @param string $method
     * @param array<mixed>  $arguments
     *
     * @return AbstractFilter|$this
     */
    public function __call($method, $arguments)
    {
        if ($filter = $this->resolveFilter($method, $arguments)) {
            return $this->addFilter($filter);
        }

        return $this;
    }
}
