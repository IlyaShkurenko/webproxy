<div id="dbconnect" class="successbox">
    <strong><span class="title">Successfully connected to database</span></strong><br>
    You can try start migration.
</div>

<div id="finish" class="successbox" style="display: none">
    <strong><span class="title">Successfully migrate users and products</span></strong><br>
    Migrate process is finished!
</div>

<div id="error" class="errorbox" style="display: none">
    <strong><span class="title">Error by migrate process</span></strong><br>
    An error occurred during migrate process!
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<div class="btn-container">
    <button id="start" class="btn btn-success"><i class="fa fa-check"></i> Start migration</button>
    <button id="stop" class="btn btn-danger" style="display: none;"><i class="fa fa-close"></i> Stop migration</button>
    <button id="continue" class="btn btn-default" style="display: none;"><i class="fa fa-hand-pointer-o"></i> Continue migration</button><br>
</div>
<div id="block" style="display: none">
    <h2 style="text-align: center;"><b>Migrate status</b></h2>
    <div id="progressblock" style='width: 100%; background-color: #ddd;'>
        <div id="progress" style='width: 0;height: 30px;background-color: #4CAF50;text-align: center;line-height: 30px;color: white;'>
            0%
        </div>
    </div>
    <div class="btn-container">
        <p>Users count: <span id="users_count">0</span> | Users imported: <span id="users">0</span> | Products imported: <span id="products">0</span> | Processed users: <span id="skip">0</span></p>
    </div>
</div>
<script>
    var skip_count = 0;
    var progress = 0;
    var users_count = 0;
    var users = 0;
    var products = 0;
    var stop = false;

    $('#stop').click(function () {
        if(confirm('Are you sure?')) {
            stop = true;
            $('#stop').hide();
            $('#continue').show();
        }
    });

    $('#continue').click(function () {
        if(confirm('Are you sure?')) {
            stop = false;
            $('#continue').hide();
            $('#stop').show();

            ajaxRequest();
        }
    });

    $('#start').click(function () {
        if(confirm('Are you sure?')) {
            $('#block').show();
            $('#stop').show();
            $('#start').hide();
            $('#dbconnect').hide();

            ajaxRequest();
        }
    });

    function  createData() {
        if(users_count == 0) {
            return {skip : skip_count};
        } else {
            return {skip : skip_count, users_count: true}
        }

    }

    function ajaxRequest() {
        if(!stop && progress < 100) {
            var request = $.ajax({
                url: "<?php print $vars['modulelink'] ?>",
                type: "POST",
                data: createData(),
                dataType: "html"
            });

            request.done(function(data) {

                try {
                    data = JSON.parse(data);
                } catch (e) {
                    $('#error').show();
                    $('#progressblock').hide();
                    $('#stop').hide();
                    $('#continue').hide();
                    return true;
                }

                skip_count = data.skip;
                users = users + data.users;
                products = products + data.products;

                if(data.users_count != 0) {
                    users_count = data.users_count;
                    $('#users_count').text(users_count);
                }

                progress = (skip_count / (users_count / 100)).toFixed(1);

                $('#progress').text(progress + '%').css('width', progress + '%');
                $('#users').text(users);
                $('#products').text(products);
                $('#skip').text(skip_count);

                console.log(data);

                if(progress >= 100) {
                    $('#finish').show();
                    $('#progressblock').hide();
                    $('#stop').hide();
                    $('#continue').hide();
                    return true;
                }

                ajaxRequest();
            });

            request.fail(function(jqXHR, textStatus) {
                console.log(textStatus);
                $('#error').show();
                $('#progressblock').hide();
                $('#stop').hide();
                $('#continue').hide();
                return true;
            });
        }
    }
</script>
