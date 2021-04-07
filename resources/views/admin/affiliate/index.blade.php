@extends('layouts.admin')

@section('title')
    <title>Affiliate</title>
@endsection

@section('css')
    <style>
        .hide {
            display: none
        }
    </style>
@endsection

@section('content')
<main class="main">
    <div class="container-fluid" style="padding: 0px 0px 0px 0px;">
        <div class="animated fadeIn">
            <div class="col-md-12">
                <div class="card shadow mb-2">
                    <div class="card-header bg-info">
                        <span class="text-xl font-weight-bold text-white text-uppercase">Affiliate</span>
                        <button class="btn btn-primary btn-sm float-right" id="uploadAffiliate"><i class="fas fa-file-csv mr-1"></i> Upload Data Affiliate</button>
                    </div>
                    <div class="card-body">
                        <div class="hide" id="failed">
                            <div class="alert alert-danger" id="errorMessage"></div>
                        </div>
                        <fieldset class="hide" id="affiliateUploadForm">
                            <form id="uploadForm">
                                <div class="card py-3 mb-shadow-2">
                                    <div class="col-md-12">
                                        <input type="file" name="affiliateFile" id="uploadFile">
                                        <div class="mt-2 col-md-3 p-0">
                                            <button type="submit" class="btn btn-success btn-sm">Simpan</button>
                                            <a href="download-template" class="btn btn-info btn-sm">Download Template</a>
                                            <div class="float-right mr-5 hide" id="loader">
                                                <img src="/uploads/spinner.gif" width="35px" height="35px"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <hr>
                        </fieldset>
                        <div class="col-md-12">
                            <table class="table table-hover table-outline" id="affiliate_list" style="font-size: 13px;">
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @extends('admin.affiliate._modal_detail_affiliate')
</main>
@endsection

@section('js')
<script>
    $(async function() {
        let affiliateList;
        let detailTransactionAffiliate;
        let upload = false;

        dataAffiliateList();

        $('body').on('click', "#uploadAffiliate", function() {
            if (upload === false) {
                $('#affiliateUploadForm').removeClass('hide')
                upload = true
            } else {
                $('#affiliateUploadForm').addClass('hide')
                upload = false
            }
        })

        $('body').on('click', '#vendor-button', function() {
            affiliateList.destroy();
            dataVendorList();
        });

        $('body').on('click', '#affiliate-button', function() {
            affiliateList.destroy();
            dataAffiliateList();
        });

        $('body').on('click', '#detailAffiliate', function() {
            let id_user = $(this).attr('data-id');
            let id_vendor = $(this).attr('data-vendor');
            
            $.ajax({
                url: "{{ route('affiliate.detail') }}",
                method: "GET",
                datatype: "JSON",
                data:{
                    user_id: id_user,
                    vendor_id: id_vendor,
                },
                success: function(res) {
                    $('#modal-detail-affiliate').modal('show');
                    $('#id').html(res.detail.user_id);
                    $('#balance').html(res.detail.balance);
                    $('#click').html(res.detail.click);

                    $('#username').html(res.detail.username_aff);
                    $('#commission').html(res.detail.commission);
                    $('#signup').html(res.detail.signup);

                    $('#no_telepon').html(res.detail.no_telepon);
                    $('#transaction').html(res.detail.transaction_count);
                    $('#conversion').html(res.detail.conversion);

                    $('#email').html(res.detail.email);

                    generateTransactionAffiliate(id_user, id_vendor);
                }
            })
        });

        $('body').on('submit', '#uploadForm', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);

            $.ajax({
                url: "{{ route('affiliate.upload') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#affiliateUploadForm').attr('disabled', true)
                    $('#loader').removeClass('hide')
                },
                success: function (res) {
                    if (res.code == 200 && res.data === '') {
                        $.notify(res.message, "success");
                        setNullInputFile()
                        affiliateList.ajax.reload()
                    } else if (res.code == 200 && res.data !== '') {
                        $.notify(res.message, "info");
                        $('#failed').removeClass('hide')
                        $('#errorMessage').html(res.message)
                        affiliateList.ajax.reload()
                    } else {
                        $.notify(res.message, "danger");
                        $('#failed').removeClass('hide')
                        $('#errorMessage').html(res.message)
                    }
                }
            })
        })

        $('body').on('click', '#resendEmailLink', function() {
            let userID = $(this).data('id')
            
            $.ajax({
                url: "{{ route('affiliate.resendEmail') }}",
                method: "GET",
                datatype: "JSON",
                data: {
                    userID: userID
                },
                beforeSend: function() {
                    $(`.btnlink_${userID}`).attr('disabled', true)
                },
                success: function (res) {
                    if (res.code == 200) {
                        $.notify(res.message, "success")
                        $(`.btnlink_${userID}`).attr('disabled', false)
                    }
                }
            })
        })

        function setNullInputFile() {
            $('#affiliateUploadForm').attr('disabled', false)
            $('#loader').addClass('hide')
            
            $('#affiliateUploadForm').addClass('hide')
            $('#uploadFile').val('')
            upload = false
        }

        function dataAffiliateList() {
            affiliateList = $('#affiliate_list').DataTable({
                processing: true,
                serverside: true,
                bLengthChange : false,
                dom: '<"toolbar">frtip',
                initComplete: function(){
                    $("div.toolbar")
                        .html('<button id="vendor-button" style="float: left;" class="btn btn-dark btn-sm" style="margin-left: 15px;">Data Per Vendor</button>');
                    $("#affiliate_list").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
                },
                ajax: {
                    url: "{{ route('datatableAffiliateAdmin') }}",
                },
                columnDefs: [
                    { orderable: false, targets: [0, 3, 4, 5, 6, 7] },
                ],
                columns: [
                    {
                        title: 'No',
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                    },  
                    {
                        title: 'Nama Lengkap',
                        data: 'username',
                        name: 'username'
                    },
                    {
                        title: 'Balance',
                        data: 'balance',
                        name: 'balance'
                    },
                    {
                        title: 'Commission',
                        data: 'commission',
                        name: 'commission'
                    },
                    {
                        title: 'Click',
                        data: 'click',
                        name: 'click'
                    },
                    {
                        title: 'Signup',
                        data: 'signup',
                        name: 'signup'
                    },
                    {
                        title: 'Conversion',
                        data: 'conversion',
                        name: 'conversion'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    }
                ],
            });
        }

        function dataVendorList() {
            affiliateList = $('#affiliate_list').DataTable({
                processing: true,
                serverside: true,
                bLengthChange : false,
                dom: '<"toolbar">frtip',
                initComplete: function(){
                    $("div.toolbar")
                        .html('<button id="affiliate-button" style="float: left;" class="btn btn-dark btn-sm" style="margin-left: 15px;">Data Per User</button>');           
                    $("#affiliate_list").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
                },
                ajax: {
                    url: "{{ route('datatableAffiliateVendorAdmin') }}",
                },
                columnDefs: [
                    { orderable: false, targets: [0, 3, 4, 5, 6, 7] },
                ],
                columns: [
                    {
                        title: 'No',
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                    },  
                    {
                        title: 'Username',
                        data: 'username',
                        name: 'username'
                    },
                    {
                        title: 'Vendor',
                        data: 'vendor_name',
                        name: 'vendor_name'
                    },
                    {
                        title: 'Commission',
                        data: 'commission',
                        name: 'commission'
                    },
                    {
                        title: 'Click',
                        data: 'click',
                        name: 'click'
                    },
                    {
                        title: 'Signup',
                        data: 'signup',
                        name: 'signup'
                    },
                    {
                        title: 'Conversion',
                        data: 'conversion',
                        name: 'conversion'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    }
                ],
            });
        }

        function generateTransactionAffiliate(id_user, id_vendor) {
            $('#detail_transaction_affiliate').DataTable().destroy();
            
            detailTransactionAffiliate = $('#detail_transaction_affiliate').DataTable({
                processing: true,
                serverside: true,
                bLengthChange : false,
                searching: false,
                ajax: {
                    url: "{{ route('datatableDetailAffiliateAdmin') }}",
                    data: function(d) {
                        d.user_id = id_user,
                        d.vendor_id = id_vendor
                    }
                },
                columnDefs: [
                    { orderable: false, targets: [0, 4, 5, 6, 7] },
                ],
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                    },  
                    {data: 'customer_name', name: 'customer_name'},
                    {data: 'product_service', name: 'product_service'},
                    {data: 'vendor_name', name: 'vendor_name'},
                    {data: 'transaction_date', name: 'transaction_date'},
                    {data: 'amount', name: 'amount'},
                    {data: 'commission', name: 'commission'},
                    {data: 'status', name: 'status'}
                ],
            });
        }
    });
</script>
@endsection