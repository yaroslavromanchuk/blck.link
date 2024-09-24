<?php

namespace backend\widgets;

use yii\base\Widget;
use backend\models\Invoice;

class CreateInvoice extends Widget {

    public Invoice $invoice;
    public function init() {
        $this->invoice = new Invoice();
    }

    public function run() {
        return $this->render('_createInvoice', [
            'invoice' => $this->invoice,
        ]);
    }
}