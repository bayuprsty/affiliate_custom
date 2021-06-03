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
                        <fieldset class="hide" id="affiliateUploadForm">
                            <form id="uploadForm">
                                
                            </form>
                            <div class="card py-3 mb-shadow-2">
                                <div class="col-md-12">
                                    <input type="file" name="affiliateFile" id="uploadFile">
                                    <div class="mt-2 col-md-3 p-0">
                                        <button type="button" class="btn btn-success btn-sm" id="uploadProgress">Simpan</button>
                                        <a href="download-template" class="btn btn-info btn-sm">Download Template</a>
                                    </div>
                                    <div class="mt-2 hide" id="progressbar">
                                        <div class="progress active">
                                            <div class="progress-bar bg-info progress-bar-striped" role="progressbar" aria-valuemin="0" aria-valuemax="100" id="upload-progress" style="width: 0%">
                                                <span id="inner-progress" class="text-white font-weight-bold"></span>
                                            </div>
                                        </div>
                                        <div class="text-right font-weight-bold">
                                            <span>
                                                <span id="complete" class="text-danger">0</span> / <span id="length" class="text-success">0</span> Complete
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                        </fieldset>
                        <fieldset id="tableAffiliate">
                            <div class="row">
                                <div class="col-md-6">
                                    <button class="btn btn-danger btn-sm" id="resendEmailLink"><i class="fas fa-envelope"></i> Resend Email Link Affiliate</button>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button class="btn btn-success btn-sm" id="exportData">Export</button>
                                </div>
                            </div>
                            <hr>
                            <div class="col-md-12">
                                <table class="table table-hover table-outline" id="affiliate_list" style="font-size: 13px;">
                                </table>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @extends('admin.affiliate._modal_detail_affiliate')
</main>
@endsection

@section('js')
<script type="text/javascript">
    $(function() {
        let affiliateList;
        let detailTransactionAffiliate;
        let upload = false;
        let allChecked = false;
        let resendSuccess = 0

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

        // $('body').on('submit', '#uploadForm', function(e) {
        //     e.preventDefault();
            
        //     var formData = new FormData(this);

        //     $.ajax({
        //         url: "{{ route('affiliate.upload') }}",
        //         method: "POST",
        //         data: formData,
        //         contentType: false,
        //         processData: false,
        //         beforeSend: function() {
        //             $('#affiliateUploadForm').attr('disabled', true)
        //             $('#loader').removeClass('hide')
        //         },
        //         send: function() {
        //             timeAllData = setTimeout(function(){
        //                 $.get('/get-upload-data', function(data){
        //                     if (data !== '') {
        //                         $('#allData').html(data);
        //                         clearTimeout(timeAllData);
        //                         return false;
        //                     }
        //                 })
        //             }, 1000)
                    
        //             timeProgress = setTimeout(function () {
        //                 $.get('/get-success-upload', function(data){
        //                     $('#process').html(data);
        //                     if (data === allDataUpload) {
        //                         clearTimeout(timeProgress);
        //                     }
        //                 })
        //             }, 2000)
        //         },
        //         success: function (res) {
        //             if (res.code == 200 && res.data === '') {
        //                 $.notify(res.message, "success");
        //                 window.setTimeout(function(){
        //                     location.reload()
        //                 }, 1000)
        //             } else if (res.code == 200 && res.data !== '') {
        //                 $.notify(res.message, "info");
        //                 window.setTimeout(function(){
        //                     location.reload()
        //                 }, 1000)
        //             } else {
        //                 $.notify(res.message, "danger")
        //             }
        //         }
        //     })
        // })

        $('#uploadProgress').bind('click', function() {
            let regex = /^([a-zA-Z0-9\s_\\.\-:])+(.csv)$/;
            if (regex.test($('#uploadFile').val().toLowerCase())) {
                $('#affiliateUploadForm').attr('disabled', true)
                $('#progressbar').removeClass('hide');

                let uploadProgress = document.getElementById('upload-progress');

                let file = document.querySelector('#uploadFile').files[0];
                let reader = new FileReader();

                reader.readAsText(file);

                reader.onload = function (e) {
                    let csv = e.target.result;

                    let rows = csv.replace(/^\s+|\s+$/g,"").split("\r\n");
                    let length = rows.length - 1;

                    $('#length').html(length);

                    let percentProgress = (100 / length).toFixed(2);
                    let successUpload = 0;
                    let percentAnimate = 0;
                    
                    for (let line = 1; line < rows.length; line++) {
                        if (rows[line] !== '') {
                            let data = rows[line].split(',');
                            let affiliate = {
                                nama_depan: data[0],
                                nama_belakang: data[1],
                                email: data[2],
                                nomor_telepon: data[3],
                                nomor_rekening: data[4],
                                atasnama_rekening: data[5],
                                nama_bank: data[6]
                            }

                            $.ajax({
                                url: "{{ route('affiliate.uploadData') }}",
                                method: "POST",
                                datatype: "JSON",
                                data: affiliate,
                                success: function(res) {
                                    successUpload++;

                                    percentAnimate = Math.round(successUpload * percentProgress);
                                    uploadProgress.style.width = percentAnimate + '%';

                                    $('#inner-progress').html(`${percentAnimate}%`);
                                    $('#complete').html(successUpload);

                                    if (successUpload === length) {
                                        $.notify(`${successUpload} data berhasil diupload`, 'success');
                                        affiliateList.ajax.reload();

                                        allSuccess();

                                        setTimeout(() => {
                                            $('#progressbar').addClass('hide');

                                            $('#affiliateUploadForm').addClass('hide');
                                            upload = false;

                                            $('#affiliateUploadForm').attr('disabled', false)
                                        }, 2000);
                                    }
                                }
                            })
                        }
                    }
                }
            }
        })

        function allSuccess() {
            $('#complete').removeClass('text-danger').addClass('text-success');
            $('#upload-progress').removeClass('bg-info').addClass('bg-success');
        }

        // Check Box Affiliate

        $('#checkAll').on('click', function(e) {
            allChecked = !allChecked
            $('input[type="checkbox"]').prop('checked', allChecked)
        })

        $('body').on('click', '#resendEmailLink', async() => {
            let dataChecked = $('input[type="checkbox"]:checked')
            let fieldset = document.getElementById('tableAffiliate')
            let arrayID = []

            if (dataChecked.length == 0) {
                $.notify("Tidak ada data yang dicentang. Silahkan pilih / centang User terlebih dahulu", "error");
            } else {
                dataChecked.each(function() {
                    let userID = $(this).val()
                    if (userID !== "") {
                        arrayID.push($(this).val())
                    }
                })

                $.ajax({
                    url: "{{ route('affiliate.resendEmail') }}",
                    method: "GET",
                    datatype: "JSON",
                    data: {
                        arrayID: arrayID
                    },
                    beforeSend: function() {
                        fieldset.disabled = true
                    },
                    success: function (res) {
                        if (res.code == 200) {
                            $.notify(res.message, "success")
                            fieldset.disabled = false
                        }
                    }
                })
            }
        })

        $('body').on('click', '#exportData', function() {
            if(affiliateList.rows().data().length == 0) {
                $.notify("Can't export as CSV. Data is Empty", {type:"warning"});
                return false;
            }
            
            status = $('#status_id').val() ? $('#status_id').val() : 'all';
            dateStart = $('#dateStart').val() ? $('#dateStart').val() : 'all';
            dateEnd = $('#dateEnd').val() ? $('#dateEnd').val() : 'all';

            window.open("export-csv", "_blank");
        });

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
                bLengthChange : true,
                ajax: {
                    url: "{{ route('datatableAffiliateAdmin') }}",
                },
                columnDefs: [
                    { orderable: false, targets: [0, 3, 4, 5, 6, 7] },
                ],
                columns: [
                    {
                        title: `<input type="checkbox" class="icheckbox_square mr-2" id="checkAll" value="">All`,
                        data: 'checkbox',
                        name: 'checkbox',
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