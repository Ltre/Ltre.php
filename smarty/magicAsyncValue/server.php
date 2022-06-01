<?php

class MagicController extends BaseController {

    function actionAsync($req){
        @exit(Utils::magicAsyncValue($_REQUEST['id'], 'get'));
    }

}
