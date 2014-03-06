<?php

namespace Leptir\ErrorReport;

interface ErrorReportInterface
{
    public function reportException(\Exception $ex);

    public function reportErrorMessage($message);

    public function addErrorData(array $data);
}
