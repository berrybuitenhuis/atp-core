<?php

namespace AtpCore\Api\VWE\Response;

class VehicleData
{
    /** @var RdwInfoAdvanced */
    public $rdwInfoAdvanced;
    /** @var AtlTransmissie|null */
    public $atlTransmissie;
    /** @var AtlFoto|null */
    public $atlFoto;
    /** @var AtlMmtInfo */
    public $atlMmtInfo;
}