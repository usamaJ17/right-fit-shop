@extends('layouts.back-end.app')

@section('title', translate('sub_Sub_Category'))

@section('content')
    <div class="content container-fluid">

        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ asset('public/assets/back-end/img/brand-setup.png') }}" alt="">
                {{ translate('sub_Sub_Category_Setup') }}
            </h2>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-start">
                        <form action="{{ route('admin.sub-sub-category.store') }}" method="POST">
                            @csrf
                                <ul class="nav nav-tabs w-fit-content mb-4">
                                    @foreach($languages as $lang)
                                        <li class="nav-item text-capitalize">
                                            <span class="nav-link form-system-language-tab cursor-pointer {{ $lang == $defaultLanguage? 'active':''}}"
                                               id="{{ $lang}}-link">{{ucfirst(getLanguageName($lang)).'('.strtoupper($lang).')'}}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="row">
                                @foreach($languages as $lang)
                                    <div
                                        class="col-12 form-group {{ $lang != $defaultLanguage ? 'd-none':''}} form-system-language-form"
                                        id="{{ $lang}}-form">
                                        <label class="title-color"
                                               for="exampleFormControlInput1">{{ translate('sub_sub_category_name') }}
                                            <span class="text-danger">*</span>
                                            ({{strtoupper($lang) }})</label>
                                        <input type="text" name="name[]" class="form-control"
                                               placeholder="{{ translate('new_Sub_Sub_Category') }}" {{ $lang == $defaultLanguage? 'required':''}}>
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{ $lang}}">
                                @endforeach

                                    <input name="position" value="2" class="d-none">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label
                                                class="title-color">{{ translate('main_Category') }}
                                                <span class="text-danger">*</span></label>
                                            <select class="form-control action-get-sub-category-onchange"
                                                    id="main-category" required data-route="{{ route('admin.sub-sub-category.getSubCategory') }}">
                                                <option value="" disabled
                                                        selected>{{ translate('select_main_category') }}</option>
                                                @foreach($parentCategories as $category)
                                                    <option
                                                        value="{{ $category['id']}}">{{ $category['defaultName']}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="title-color text-capitalize" for="name">
                                                {{ translate('sub_category_Name') }}<span
                                                    class="text-danger">*</span>
                                            </label>
                                            <select name="parent_id" id="parent_id" class="form-control">
                                                <option value="" disabled selected>
                                                    {{ translate('select_sub_category_first') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="title-color text-capitalize" for="priority">
                                                {{ translate('priority') }}
                                                <span>
                                                    <i class="tio-info" title="{{ translate('the_lowest_number_will_get_the_highest_priority') }}"></i>
                                                </span>
                                            </label>
                                            <select class="form-control" name="priority" id="" required>
                                                <option disabled selected>{{ translate('set_Priority') }}</option>
                                                @for ($increment = 0; $increment <= 10; $increment++)
                                                    <option value="{{ $increment }}">{{ $increment }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            <button type="reset"
                                                    class="btn btn-secondary">
                                                {{ translate('reset') }}
                                            </button>
                                            <button type="submit" class="btn btn--primary">
                                                {{ translate('submit') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-20" id="cate-table">
            <div class="col-md-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="row align-items-center">
                            <div class="col-sm-5 col-md-6 col-lg-8 mb-2 mb-sm-0">
                                <h5 class="text-capitalize d-flex gap-2">
                                    {{ translate('sub_sub_category_list') }}
                                    <span
                                        class="badge badge-soft-dark radius-50 fz-12">{{ $categories->total() }}</span>
                                </h5>
                            </div>
                            <div class="col-sm-7 col-md-6 col-lg-4">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group input-group-custom input-group-merge">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="searchValue"
                                               class="form-control"
                                               placeholder="{{ translate('search_by_Sub_Sub_Category') }}"
                                               aria-label="Search orders" value="{{ request('searchValue') }}" required>
                                        <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-start">
                            <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>{{ translate('ID') }}</th>
                                <th>{{ translate('sub_sub_category_name') }}</th>
                                <th>{{ translate('priority') }}</th>
                                <th class="text-center">{{ translate('action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($categories as $key=>$category)
                                <tr>
                                    <td>{{ $category['id']}}</td>
                                    <td>{{ $category['defaultName']}}</td>
                                    <td>{{ $category['priority']}}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a class="btn btn-outline-info btn-sm square-btn"
                                               title="{{ translate('edit') }}"
                                               href="{{ route('admin.sub-sub-category.update',[$category['id']]) }}">
                                                <i class="tio-edit"></i>
                                            </a>
                                            <a class="btn btn-outline-danger btn-sm square-btn category-delete-button"
                                               title="{{ translate('delete') }}"
                                               id="{{ $category['id']}}">
                                                <i class="tio-delete"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="table-responsive mt-4">
                        <div class="d-flex justify-content-lg-end">
                            {{ $categories->links() }}
                        </div>
                    </div>

                    @if(count($categories)==0)
                        <div class="text-center p-4">
                            <img class="mb-3 w-160"
                                 src="{{ asset('public/assets/back-end/svg/illustrations/sorry.svg') }}"
                                 alt="{{translate('image_description')}}">
                            <p class="mb-0">{{ translate('no_data_to_show') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <span id="route-admin-category-delete" data-url="{{ route('admin.sub-sub-category.delete') }}"></span>
@endsection

@push('script')
    <script src="{{ asset('public/assets/back-end/js/products-management.js') }}"></script>
@endpush
