<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Processos</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Processos</a></li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<div class="content">
    <div class="container-fluid">
    </div><!-- /.container-fluid -->
</div>
<!-- /.content -->
<script>
    $(document).ready(function() {

        $.ajax({
            url: "ajax/processos.ajax.php",
            method:'POST',
            dataType: 'json',
            success: function(response) {
                console.log("reponse", response);
            }

        });

    });
</script>