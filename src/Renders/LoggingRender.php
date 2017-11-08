<?php

namespace Ebola\Logging\Renders;

use Ebola\Logging\Logging;

use Validator;
use Carbon\Carbon;

class LoggingRender extends Logging
{
    private $logging;

    public function __construct($user=null, $fields=null, $rowCount=null)
    {
        $this->logging = new Logging($user, $fields, $rowCount);
    }

    public function renderUserLogging()
    {
        $request = request();

        $rows = $this->logging->getRows();

        // if ($request->ajax()) {
            
        // }

        $rows      = $rows->paginate($this->logging->getRowCount());

        $fields           = $this->logging->getFields();
        $translatedFields = $this->logging->getTranslatedFields();

        if ($request->ajax()) {
            return [
                    'pagination' => view('logging::_pagination', compact('rows'))->render(),
                    'table'      => view('logging::_table', compact('rows', 'fields', 'translatedFields'))->render(),
            ];
        }

        return view('logging::user_logging', compact('rows', 'fields', 'translatedFields'));
    }

    public function renderDownloadLogging()
    {
        $request = request();

        if ($request->has('date_start') || $request->has('date_end')) {
            $validator = Validator::make($request->all(), [
                'date_start' => 'required|date',
                'date_end'   => 'required|date|after:date_start',
            ]);

            if ($validator->fails()) {
                return view('logging::download_logging')->withErrors($validator);
            } else {
                $startDate = Carbon::parse($request->date_start)->startOfDay()->format('Y-m-d H:i:s');
                $endDate   = Carbon::parse($request->date_end)->endOfDay()->format('Y-m-d H:i:s');

                $rows = $this->logging->getRows(); 
                $rows = $rows->whereBetween('created_at', array($startDate, $endDate));
                $rows = $rows->get();

                $this->logging->getLoggingFile($rows);
            }
        }

        return view('logging::download_logging');
    }
}
