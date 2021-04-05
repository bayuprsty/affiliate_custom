@extends('layouts.admin')

@section('title')
    <title>Lead</title>
@endsection

@section('content')
<main class="main">
    <div class="container-fluid" style="padding: 0px 0px 0px 0px;">
        <div class="animated fadeIn">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header bg-info">
                        <span class="text-xl font-weight-bold text-white text-uppercase mb-1">Lead</span>
                    </div>
                    <div class="card-body">
                        <div class="row" style="margin: 0 auto">
                            <div class="col-md-2">
                                <select name="status_id" class="form-control form-control-sm" id="status_id">
                                    <option value="all">-- Select Status --</option>
                                    <option value="1">ON PROGRESS</option>
                                    <option value="2">SUCCESS</option>
                                    <option value="3">CANCELED</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group">
                                    <input type="date" class="form-control form-control-sm" id="dateStart">
                                </div>
                            </div>
                            <div class="col-md-1 text-center">
                                to
                            </div>
                            <div class="col-md-2">
                                <div class="input-group">
                                    <input type="date" class="form-control form-control-sm" id="dateEnd">
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <button class="btn btn-primary btn-sm" id="searchData">Search</button>
                                <button class="btn btn-light btn-sm" id="resetData">Reset</button>
                            </div>
                            <div class="col text-right">
                                <button class="btn btn-success btn-sm" id="exportData">Export</button>
                            </div>
                        </div>
                        <hr>
                        <div class="col-md-12">
                            <table class="table table-hover table-outline" id="leadAff_list" style="font-size: 13px;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Customer Name</th>
                                        <th>Vendor</th>
                                        <th>Email</th>
                                        <th>No Telepon</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('js')
<script type="text/javascript">
    $(document).ready(function() {
        var leadAffList = $('#leadAff_list').DataTable( {
            processing: true,
            serverside: true,
            bLengthChange : false,
            searching: false,
            ajax: {
                url: "{{ route('datatableLeadAffiliate') }}",
                data: function(d) {
                    d.status = $('#status_id').val();
                    d.dateStart = $('#dateStart').val();
                    d.dateEnd = $('#dateEnd').val();
                }
            },
            columnDefs: [
                { orderable: false, targets: [0, 3, 4, 6] },
            ],
            columns: [
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                },  
                {data: 'customer_name', name: 'customer_name'},
                {data: 'vendor_id', name: 'vendor_id'},
                {data: 'email', name: 'email'},
                {data: 'no_telepon', name: 'no_telepon'},
                {data: 'date', name: 'date'},
                {data: 'status', name: 'status'},
            ],
            initComplete: function () {  
                $("#leadAff_list").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");            
            },
        } );

        $('#searchData').click(function() {
            leadAffList.ajax.reload();
        });

        $('#resetData').click(function() {
            $('#status_id').val('');
            $('#dateStart').val('');
            $('#dateEnd').val('');
            leadAffList.ajax.reload();
        });

        $('body').on('click', '#exportData', function() {
            if(leadAffList.rows().data().length == 0) {
                $.notify("Can't export as PDF. Data is Empty", {type:"warning"});
                return false;
            }
            
            status = $('#status_id').val() ? $('#status_id').val() : 'all';
            dateStart = $('#dateStart').val() ? $('#dateStart').val() : 'all';
            dateEnd = $('#dateEnd').val() ? $('#dateEnd').val() : 'all';

            window.open("downloadPdfLead/" + status + "/" + dateStart + "/" + dateEnd, "_blank");
        });
    });
</script>
@endsection