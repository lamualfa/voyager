@if (isset($options->model) && isset($options->type))

    @if (class_exists($options->model))

        @php $relationshipField = $row->field; @endphp

        @if ($options->type == 'belongsTo')

            @if (isset($view) && ($view == 'browse' || $view == 'read'))

                @php
                    $relationshipData = isset($data) ? $data : $dataTypeContent;
                    $model = app($options->model);
                    $primaryKey = $model->getKeyName();
                    $relationDataType = Voyager::model('DataType')
                        ->where('model_name', $options->model)
                        ->first(['slug']);
                    
                    $row = $model::where($options->key, $relationshipData->{$options->column})->first();
                @endphp

                @if (isset($row))
                    <p>
                        @if (empty($relationDataType))
                            {{ $row->{$options->label} }}
                        @else
                            <a
                                href="{{ $view === 'browse' ? '.' : '..' }}/{{ $relationDataType->slug }}/{{ $row->{$primaryKey} }}">{{ $row->{$options->label} }}</a>
                        @endif
                    </p>
                @else
                    <p>{{ __('voyager::generic.no_results') }}</p>
                @endif
            @else
                <select class="form-control select2-ajax" name="{{ $options->column }}"
                    data-get-items-route="{{ route('voyager.' . $dataType->slug . '.relation') }}"
                    data-get-items-field="{{ $row->field }}"
                    @if (!is_null($dataTypeContent->getKey())) data-id="{{ $dataTypeContent->getKey() }}" @endif
                    data-method="{{ !is_null($dataTypeContent->getKey()) ? 'edit' : 'add' }}"
                    @if ($row->required == 1) required @endif>
                    @php
                        $model = app($options->model);
                        $query = $model::where($options->key, old($options->column, $dataTypeContent->{$options->column}))->get();
                    @endphp

                    @if (!$row->required)
                        <option value="">{{ __('voyager::generic.none') }}</option>
                    @endif

                    @foreach ($query as $relationshipData)
                        <option value="{{ $relationshipData->{$options->key} }}"
                            @if (old($options->column, $dataTypeContent->{$options->column}) == $relationshipData->{$options->key}) selected="selected" @endif>
                            {{ $relationshipData->{$options->label} }}</option>
                    @endforeach
                </select>

            @endif
        @elseif($options->type == 'hasOne')
            @php
                $relationshipData = isset($data) ? $data : $dataTypeContent;
                
                $model = app($options->model);
                $query = $model::where($options->column, '=', $relationshipData->{$options->key})->first();
                
            @endphp

            @if (isset($query))
                <p>{{ $query->{$options->label} }}</p>
            @else
                <p>{{ __('voyager::generic.no_results') }}</p>
            @endif
        @elseif($options->type == 'hasMany')
            @php
                $model = app($options->model);
                $primaryKey = $model->getKeyName();
                $relationDataType = Voyager::model('DataType')
                    ->where('model_name', $options->model)
                    ->first(['slug', 'details']);
                $orderField = $options->sort->field ?? ($relationDataType->order_column ?? null);
                $orderDirection = $options->sort->direction ?? ($relationDataType->order_direction ?? null);
            @endphp

            @if (isset($view) && ($view == 'browse' || $view == 'read'))

                @php
                    $relationshipData = $data ?? $dataTypeContent;
                    $query = $model::where($options->column, '=', $relationshipData->{$options->key});
                    if (!empty($orderField) && !empty($orderDirection)) {
                        $query = $query->orderBy($orderField, $orderDirection);
                    }
                    
                    $selected_values = $query->get();
                @endphp

                @if ($view == 'browse')
                    @if (empty($selected_values))
                        <p>{{ __('voyager::generic.no_results') }}</p>
                    @else
                        @php
                            $string_values = $selected_values->pluck($options->label)->join(',');
                            if (mb_strlen($string_values) > 25) {
                                $string_values = mb_substr($string_values, 0, 25) . '...';
                            }
                        @endphp
                        <p>{{ $string_values }}</p>
                    @endif
                @else
                    @if (empty($selected_values))
                        <p>{{ __('voyager::generic.no_results') }}</p>
                    @else
                        <ul>
                            @foreach ($selected_values as $selected_value)
                                <li>
                                    @if (empty($relationDataType))
                                        {{ $selected_value->{$options->label} }}
                                    @else
                                        <a
                                            href="../{{ $relationDataType->slug }}/{{ $selected_value->{$primaryKey} }}">{{ $selected_value->{$options->label} }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            @else
                @php
                    $query = $model::where($options->column, '=', $dataTypeContent->{$options->key});
                    if (!empty($orderField) && !empty($orderDirection)) {
                        $query = $query->orderBy($orderField, $orderDirection);
                    }
                    
                    $rows = $query->get();
                @endphp

                @if ($rows->isNotEmpty())
                    <ul>
                        @foreach ($rows as $row)
                            <li>
                                @if (empty($relationDataType))
                                    {{ $row->{$options->label} }}
                                @else
                                    <a target="_blank"
                                        href="../../{{ $relationDataType->slug }}/{{ $row->{$primaryKey} }}">{{ $row->{$options->label} }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>{{ __('voyager::generic.no_results') }}</p>
                @endif

            @endif
        @elseif($options->type == 'belongsToMany')
            @if (isset($view) && ($view == 'browse' || $view == 'read'))

                @php
                    $relationshipData = isset($data) ? $data : $dataTypeContent;
                    
                    $selected_values = isset($relationshipData)
                        ? $relationshipData
                            ->belongsToMany($options->model, $options->pivot_table, $options->foreign_pivot_key ?? null, $options->related_pivot_key ?? null, $options->parent_key ?? null, $options->key)
                            ->get()
                            ->map(function ($item, $key) use ($options) {
                                return $item->{$options->label};
                            })
                            ->all()
                        : [];
                @endphp

                @if ($view == 'browse')
                    @php
                        $string_values = implode(', ', $selected_values);
                        if (mb_strlen($string_values) > 25) {
                            $string_values = mb_substr($string_values, 0, 25) . '...';
                        }
                    @endphp
                    @if (empty($selected_values))
                        <p>{{ __('voyager::generic.no_results') }}</p>
                    @else
                        <p>{{ $string_values }}</p>
                    @endif
                @else
                    @if (empty($selected_values))
                        <p>{{ __('voyager::generic.no_results') }}</p>
                    @else
                        <ul>
                            @foreach ($selected_values as $selected_value)
                                <li>{{ $selected_value }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            @else
                <select class="form-control select2-ajax @if (isset($options->taggable) && $options->taggable === 'on') taggable @endif"
                    name="{{ $relationshipField }}[]" multiple
                    data-get-items-route="{{ route('voyager.' . $dataType->slug . '.relation') }}"
                    data-get-items-field="{{ $row->field }}"
                    @if (!is_null($dataTypeContent->getKey())) data-id="{{ $dataTypeContent->getKey() }}" @endif
                    data-method="{{ !is_null($dataTypeContent->getKey()) ? 'edit' : 'add' }}"
                    @if (isset($options->taggable) && $options->taggable === 'on') data-route="{{ route('voyager.' . \Illuminate\Support\Str::slug($options->table) . '.store') }}"
                        data-label="{{ $options->label }}"
                        data-error-message="{{ __('voyager::bread.error_tagging') }}" @endif
                    @if ($row->required == 1) required @endif>

                    @php
                        $selected_keys = [];
                        
                        if (!is_null($dataTypeContent->getKey())) {
                            $selected_keys = $dataTypeContent->belongsToMany($options->model, $options->pivot_table, $options->foreign_pivot_key ?? null, $options->related_pivot_key ?? null, $options->parent_key ?? null, $options->key)->pluck($options->table . '.' . $options->key);
                        }
                        $selected_keys = old($relationshipField, $selected_keys);
                        $selected_values = app($options->model)
                            ->whereIn($options->key, $selected_keys)
                            ->pluck($options->label, $options->key);
                    @endphp

                    @if (!$row->required)
                        <option value="">{{ __('voyager::generic.none') }}</option>
                    @endif

                    @foreach ($selected_values as $key => $value)
                        <option value="{{ $key }}" selected="selected">{{ $value }}</option>
                    @endforeach

                </select>

            @endif

        @endif
    @else
        cannot make relationship because {{ $options->model }} does not exist.

    @endif

@endif
