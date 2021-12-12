<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalendarController extends Controller
{
    function generate(Request $request){
        /*
         * @params [
         * interval => Время на приём одного клиента
         * startTime => Время начала рабочего дня
         * endTime => Время конца рабочего дня
         * startDate => Дата начала генерации
         * endDate => Дата конца геренации (День включительно)
         * catches => [Массив с часами исключения в рабочем дне]
         * ]
         * Генерирует календарь исключая выходные
         */
        $startDate = new \DateTime($request->startDate);
        $endDate = new \DateTime($request->endDate);
        $answer = [];
        $AddDay = \DateInterval::createFromDateString("0-0-0");
        $AddDay->d = 1;
        $endDate->add($AddDay);
        $AddInterval = new \DateTime("0-0-0 00:00:00.0");
        $AddInterval = $AddInterval->diff(new \DateTime("0-0-0 ".$request->interval));
        $AddHour = new \DateTime("0-0-0 00:00:00.0");
        $AddHour = $AddHour->diff(new \DateTime("0-0-0 01:00:00.0"));
        for(;$startDate < $endDate;$startDate->add($AddDay)){
            if($startDate->format('D') == 'Sat' || $startDate->format('D') == 'Sun')
                continue;
            $noteTime = new \DateTime($startDate->format("d-m-Y")." ".$request->startTime);
            $endTime = new \DateTime($startDate->format("d-m-Y")." ".$request->endTime);
            for(;$noteTime < $endTime; $noteTime->add($AddInterval)){
                $catchF = false;
                foreach ($request->catches as $catch){
                    if(new \DateTime($startDate->format("d-m-Y")." ".$catch) == $noteTime){
                        $catchF = true;
                    }
                }
                if($catchF) {
                    $noteTime->add($AddHour);
                    $AddInterval->invert = true;
                    $noteTime->add($AddInterval);
                    $AddInterval->invert = false;
                    continue;
                }
                //echo new \DateTime($startDate->format("d-m-Y")." ".$catch) == $noteTime;
                echo $noteTime->format("d-m-Y H:i")."\n";
            }



        }
    }
}
