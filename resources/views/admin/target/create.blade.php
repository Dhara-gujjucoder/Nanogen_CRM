@extends('layouts.main')
@section('content')
    <style>
        .input-as-text {
            border: none;
            background: transparent;
            box-shadow: none;
            padding: 0;
            margin: 0;
            outline: none;
        }
    </style>
@section('title')
    {{ $page_title }}
@endsection

<div class="card">
    <div class="card-body">
        <form action="{{ route('target.store') }}" id="target_form" enctype="multipart/form-data" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="col-form-label">Target Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject" value="{{ old('subject') }}" class="form-control"
                            placeholder="Target Name" maxlength="255">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="col-form-label">Sales Person Name <span class="text-danger">*</span></label>
                        @if (auth()->user()->hasRole('sales'))
                            <input type="text" value="{{ auth()->user()->name }}" class="form-control" readonly>
                            <input type="hidden" name="salesman_id" value="{{ auth()->user()->id }}">
                        @else
                            <select name="salesman_id" class="form-control form-select search-dropdown">
                                <option value="">Select</option>
                                @if ($salesmans)
                                    @foreach ($salesmans as $s)
                                        <option value="{{ $s->user_id }}"
                                            {{ old('salesman_id') == $s->user_id ? 'selected' : '' }}>
                                            {{ $s->first_name . ' ' . $s->last_name }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="">No record</option>
                                @endif
                            </select>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="col-form-label">Region <span class="text-danger">*</span></label>
                        <select class="select search-dropdown" name="city_id">
                            <option value="">Select</option>
                            @if ($cities)
                                @foreach ($cities as $c)
                                    <option value="{{ $c->id }}"
                                        {{ old('city_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->city_name }}
                                    </option>
                                @endforeach
                            @else
                                <option value="">No record</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="col-form-label">Target Value <span class="text-danger">*</span></label>
                        <input type="number" name="target_value" value="{{ old('target_value') }}"
                            class="form-control" placeholder="Target Value">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="col-form-label">Start Date <span class="text-danger">*</span></label>
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                            <input type="text" name="start_date" value="{{ old('start_date') }}" id="startDate"
                                class="form-control" placeholder="DD/MM/YY">
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="col-form-label">End Date <span class="text-danger">*</span></label>
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                            <input type="text" name="end_date" value="{{ old('end_date') }}" id="endDate"
                                class="form-control" placeholder="DD/MM/YY">
                        </div>
                    </div>
                </div>


                <div class="col-md-7">
                    <div class="mb-3">
                        <div id="product-container" class="gc-target-grades">
                            <div class="d-flex align-items-center mb-2 gc-grade-labal">
                                <label class="col-md-4 col-form-label ">Grade <span class="text-danger">*</span></label>
                                <label class="col-md-4 col-form-label ">Target Percentage <span
                                        class="text-danger">*</span></label>
                                <label class="col-md-3 col-form-label ">Target Value <span
                                        class="text-danger">*</span></label>
                            </div>
                            <div class="product-group d-flex align-items-center mb-2">
                                <input type="hidden" name="dummy_grade" id="dummyValidationField" />
                                <div class="col-md-4">
                                    <select class="form-select me-2" name="grade_id[]">
                                        <option value="">Select Grade</option>
                                        @if ($grade)
                                            @foreach ($grade as $g)
                                                <option value="{{ $g->id }}"
                                                    {{ old('grade_id') == $g->id ? 'selected' : '' }}>
                                                    {{ $g->name }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">No record</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" name="percentage[]" value="{{ old('percentage') }}"
                                        class="form-control me-2" placeholder="Target Percentage">
                                </div>
                                <div class="col-md-4">
                                    <strong></strong>
                                    <input type="text" name="percentage_value[]"
                                        value="{{ old('percentage_value') }}" class="input-as-text" placeholder=""
                                        readonly hidden>

                                       <input type="text" name="textpercentage_value[]"
                                        value="{{ old('textpercentage_value') }}" class="input-as-text" placeholder="₹0"
                                        readonly>
                                </div>
                                {{-- /<div id="percentage_value"></div>  --}}

                                <button type="button" class="btn btn-danger remove-btn">Remove</button>
                            </div>
                        </div>
                        <div id="productError" class="text-danger mb-3" style="display:none;">
                            Please fill all fields in each product row.
                        </div>
                        <button type="button" class="btn btn-primary mt-2" id="add-new">Add New</button>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end">
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>


@endsection
@section('script')
<script>
    function calculateAndValidatePercentage() {
        let targetValue = parseFloat($('input[name="target_value"]').val());
        let totalPercentage = 0;
        let isValid = true;

        $('#product-container .product-group').each(function() {
            let $percentage = $(this).find('input[name="percentage[]"]');
            let $valueField = $(this).find('input[name="percentage_value[]"]');
           let $valueFieldText = $(this).find('input[name="textpercentage_value[]"]');

            // let $valueField = $(this).find('#percentage_value');
            let percentage = parseFloat($percentage.val());

            if (!isNaN(percentage)) {
                totalPercentage += percentage;
            }

            // Calculate percentage_value if both values are valid
            if (!isNaN(targetValue) && !isNaN(percentage)) {
                let result = (targetValue * percentage) / 100;
                $valueField.val(result.toFixed(0));
                $valueFieldText.val(IndianNumberFormatscript(result.toFixed(0)));
            } else {
                $valueField.val('');
                $valueFieldText.val('');
            }
        });

        // Show/hide error if total exceeds 100
        if (totalPercentage != 100) {
            $('#percentageLimitError').text('Total percentage should be 100%.').show();
            isValid = false;
        } else {
            $('#percentageLimitError').hide();
        }

        return isValid;
    }

    // Trigger calculation and validation on target value change
    $('input[name="target_value"]').on('input', function() {
        calculateAndValidatePercentage();
    });

    // Trigger calculation and validation on percentage input change
    $('#product-container').on('input', 'input[name="percentage[]"]', function() {
        calculateAndValidatePercentage();
    });

    // When adding a new row, also re-validate
    $('#add-new').on('click', function() {
        setTimeout(() => calculateAndValidatePercentage(), 100); // slight delay to let DOM update
    });
    /*************************** END *********************/

    /*** select option search functionality ***/
    $(document).ready(function() {
        $('.search-dropdown').select2({
            placeholder: "Select",
            // allowClear: true
        });
    });

    /*** datepicker ***/
    $(document).ready(function() {
        const startPicker = flatpickr("#startDate", {
            dateFormat: "d-m-Y",
            disableMobile: true,
            // maxDate: "today",
            defaultDate: "{{ old('start_date', isset($detail) ? \Carbon\Carbon::parse($detail->start_date)->format('d-m-Y') : now()->format('d-m-Y')) }}",
            onChange: function(selectedDates, dateStr, instance) {
                // Set selected start date as minDate for end date
                endPicker.set('minDate', dateStr);
                removeTodayHighlight(selectedDates, dateStr, instance);
            },
            onReady: removeTodayHighlight,
            onMonthChange: removeTodayHighlight,
            onYearChange: removeTodayHighlight,
            onOpen: removeTodayHighlight
        });

        const endPicker = flatpickr("#endDate", {
            dateFormat: "d-m-Y",
            disableMobile: true,
            // maxDate: "today",
            defaultDate: "{{ old('end_date', isset($detail) ? \Carbon\Carbon::parse($detail->end_date)->format('d-m-Y') : now()->format('d-m-Y')) }}",
            onReady: removeTodayHighlight,
            onMonthChange: removeTodayHighlight,
            onYearChange: removeTodayHighlight,
            onOpen: removeTodayHighlight
        });

        function removeTodayHighlight(selectedDates, dateStr, instance) {
            const todayElem = instance.calendarContainer.querySelector(".flatpickr-day.today");
            if (todayElem && !todayElem.classList.contains("selected")) {
                todayElem.classList.remove("today");
            }
        }
    });

    /*** END ***/


    /*** Validation ***/



    function check_total_percentage() {
        let totalPercentage = 0;
        $('input[name="percentage[]"]').each(function() {
            let val = parseFloat($(this).val()) || 0;
            totalPercentage += val;
        });
        console.log(totalPercentage);
        if (totalPercentage != 100) {
            return false;
        } else {
            return true;
        }
    }

    function check_atleast_one() {
        let count_percetage_input = $('input[name="percentage[]"]').length;
        if (count_percetage_input) {
            return true;
        } else {
            return false;
        }
    }

    $.validator.addMethod("validateGrades", function(value, element) {
        let isValid = true;

        $("#product-container .product-group").each(function() {
            let grade = $(this).find('select[name="grade_id[]"]').val();
            let percentage = $(this).find('input[name="percentage[]"]').val();
            let value = $(this).find('input[name="percentage_value[]"]').val();
            console.log(grade)
            console.log(percentage)
            console.log(value)
            if (grade === "" || percentage.trim() === "" || value.trim() === "") {
                isValid = false;
                return false; // Break the loop on first invalid group
            }
        });

        return isValid;
    }, "Please fill all grade fields properly.");

    // Hide error message when user changes any input field
    $('input[name="percentage[]"]').on('input', function() {
        $("#productError").hide(); // Hide the error message when the input changes
    });

    $("#target_form").validate({
        ignore: [],
        rules: {
            subject: {
                required: true
            },
            salesman_id: {
                required: true
            },
            city_id: {
                required: true
            },
            target_value: {
                required: true,
                number: true
            },
            start_date: {
                required: true
            },
            end_date: {
                required: true
            },
            dummy_grade: {
                validateGrades: true
            }
        },
        messages: {
            subject: "Please enter subject",
            salesman_id: "Please select a salesperson",
            city_id: "Please select a region",
            target_value: {
                required: "Please enter target value",
                number: "Please enter a valid number"
            },
            start_date: "Please select a start date",
            end_date: "Please select an end date"
        },
        submitHandler: function(form) {
            if (check_total_percentage()) {
                if (check_atleast_one()) {
                    form.submit(); //Submit only if check passes
                } else {
                    $("#productError").text(
                        'Please add atleast one target').show();
                }
            } else {
                // alert('ddd');
                $("#productError").text(
                    'Please enter a valid percentage. The total percentage should be 100%.').show();
            }
        },
        errorElement: 'span',
        errorPlacement: function(error, element) {
            if (element.attr("name") === "dummy_grade") {
                $("#productError").text(error.text()).show();
            } else if (element.hasClass('select2-hidden-accessible')) {
                error.addClass('text-danger');
                error.insertAfter(element.next('.select2'));
            } else {
                error.addClass('text-danger');
                error.insertAfter(element);
            }
        },
        success: function(label, element) {
            if ($(element).attr("name") === "dummy_grade") {
                $("#productError").hide();
            }
        },
        highlight: function(element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        },

        // // Add these to prevent real-time validation on dummy_grade
        // onkeyup: function(element) {
        //     if ($(element).attr("name") !== "dummy_grade") {
        //         $(element).valid();
        //     }
        // },
        // onfocusout: function(element) {
        //     if ($(element).attr("name") !== "dummy_grade") {
        //         $(element).valid();
        //     }
        // }
    });

    /*** END ***/





    const grades = @json($grade);
    /*** Add new Grade ***/
    function getSelectedGrades() {
        let selected = [];
        document.querySelectorAll('select[name="grade_id[]"]').forEach(select => {
            if (select.value) {
                selected.push(parseInt(select.value));
            }
        });
        return selected;
    }

    function refreshDropdowns() {
        const selectedGrades = getSelectedGrades();
        document.querySelectorAll('select[name="grade_id[]"]').forEach(select => {
            const currentValue = select.value;
            const optionsHtml = buildGradeOptions(selectedGrades.filter(id => id !== parseInt(currentValue)));
            select.innerHTML = optionsHtml;
            select.value = currentValue; // reassign current value to avoid losing selection
        });
    }

    function buildGradeOptions(selectedGrades) {
        let options = '<option value="">Select</option>';
        grades.forEach(grade => {
            if (!selectedGrades.includes(grade.id)) {
                options += `<option value="${grade.id}">${grade.name}</option>`;
            }
        });
        return options;
    }

    document.getElementById('add-new').addEventListener('click', function() {
        const selectedGrades = getSelectedGrades();
        // Collect already selected grade IDs
        document.querySelectorAll('select[name="grade_id[]"]').forEach(select => {
            if (select.value) {
                selectedGrades.push(parseInt(select.value));
            }
        });

        console.log(selectedGrades);
        const availableGrades = grades.filter(g => !selectedGrades.includes(g.id));

        let productContainer = document.getElementById('product-container');
        let newProductGroup = document.createElement('div');
        newProductGroup.classList.add('product-group', 'd-flex', 'align-items-center', 'mb-2');

        const optionsHtml = buildGradeOptions(selectedGrades);

        newProductGroup.innerHTML = `
        <div class="col-md-4">
            <select class="form-select me-2" name="grade_id[]">
                    ${optionsHtml}
                </select>
        </div>
        <div class="col-md-4">
             <input type="number" name="percentage[]" value="{{ old('percentage') }}"
                class="form-control me-2" placeholder="Target Percentage"></div><div class="col-md-4">

            
            <input type="hidden" name="percentage_value[]" value="{{ old('percentage_value') }}"
                class="input-as-text" readonly>

                <input type="text" name="textpercentage_value[]"
                                        value="{{ old('textpercentage_value') }}" class="input-as-text" placeholder="₹0"
                                        readonly>
                
                
                </div>



            <button type="button" class="btn btn-danger  remove-btn">Remove</button>
        `;
        productContainer.appendChild(newProductGroup);
        $("#productError").hide();
        // $("#dummyValidationField").valid();
    });
    document.getElementById('product-container').addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-btn')) {
            event.target.parentElement.remove();
            refreshDropdowns();
        }
    });

    // Optional: Refresh when user changes dropdown selection
    document.getElementById('product-container').addEventListener('change', function(e) {
        if (e.target.name === 'grade_id[]') {
            refreshDropdowns();
        }
    });
    /*** END ***/
</script>
@endsection
