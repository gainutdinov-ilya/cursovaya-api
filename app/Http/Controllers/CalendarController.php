<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\User;
use App\Models\Notes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    function generate(Request $request)
    {
        /*
         * @params [
         *  interval => Время на приём одного клиента
         *  startTime => Время начала рабочего дня
         *  endTime => Время конца рабочего дня
         *  startDate => Дата начала генерации
         *  endDate => Дата конца геренации (День включительно)
         *  catches => [Массив с часами исключения в рабочем дне]
         *  doctors => [Массив с id докторов для которых нужно сгенерировать талоны]
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
        $AddInterval = $AddInterval->diff(new \DateTime("0-0-0 " . $request->interval));
        $AddHour = new \DateTime("0-0-0 00:00:00.0");
        $AddHour = $AddHour->diff(new \DateTime("0-0-0 01:00:00.0"));
        for (; $startDate < $endDate; $startDate->add($AddDay)) {
            if ($startDate->format('D') == 'Sat' || $startDate->format('D') == 'Sun')
                continue;
            $noteTime = new \DateTime($startDate->format("d-m-Y") . " " . $request->startTime);
            $endTime = new \DateTime($startDate->format("d-m-Y") . " " . $request->endTime);
            for (; $noteTime->add($AddInterval) < $endTime; $noteTime->add($AddInterval)) {
                $noteTime->sub($AddInterval);
                $catchF = false;
                if ($request->catches != null) {
                    foreach ($request->catches as $catch) {
                        if (new \DateTime($startDate->format("d-m-Y") . " " . $catch) == $noteTime) {
                            $catchF = true;
                        }
                    }
                }
                if ($catchF) {
                    $noteTime->add($AddHour);
                    $AddInterval->invert = true;
                    $noteTime->add($AddInterval);
                    $AddInterval->invert = false;
                    continue;
                }

                //echo new \DateTime($startDate->format("d-m-Y")." ".$catch) == $noteTime;
                //echo $noteTime->format("d-m-Y H:i") . "\n";
                foreach ($request->doctors as $doc) {
                    $answer[] = array(
                        'doctor' => $doc,
                        'dateTime' => $noteTime->format("d-m-Y H:i"),
                        'free' => true
                    );
                }
            }
        }
        Calendar::insert($answer);
        return response()->json(["message" => "created"], 201);
    }

    function getRelevant()
    {
        $now = new \DateTime("now");
        $timestamp = new \DateInterval("PT6H");
        $now->add($timestamp);
        $doctors = [];
        $times = [];

        $tickets = Calendar::all()->where('dateTime', '>=', $now)->where('free', '==', 1)->sortBy('dateTime');
        if (Auth::user()->isAdmin()) {
            $tickets = Calendar::all()->where('free', '==', 1)->sortBy('dateTime');
        }

        foreach ($tickets as $ticket) {
            $doctors[] = $ticket->doctor();
            $times[] = $ticket->dateTime;
        }

        $doctors = collect($doctors)->unique();
        $times = collect($times)->unique();
        $answer = [];
        $dates = [];
        foreach ($times as $time){
            $dates[] = $time->format("d.m");
        }
        $dates = collect($dates)->unique();
        foreach ($doctors as $doctor) {
            $ticketsForThisDoctor = [];
            foreach ($dates as $day) {
                $count = 0;
                $ticketsForThisDay = [];
                foreach ($tickets as $ticket) {
                    if ($ticket->dateTime->format("d.m") == $day && $ticket->doctor == $doctor->id) {
                        $ticketsForThisDay[] = $ticket;
                        $count++;
                    }
                }
                $ticketsForThisDoctor[] = ["date" => $day, "count" => $count, "tickets" => $ticketsForThisDay];
            }
            $answer[] = array_merge(array_merge($doctor->toArray(), ["speciality" => $doctor->doctor()->speciality]), array("ticketsForDay" => $ticketsForThisDoctor));
        }

        return response()->json(["doctors" => $answer, "times" => $dates], 200);
    }

    function delete(Request $request)
    {
        $toDelete = Calendar::all()->where('dateTime', '>=', new \DateTime($request->startDate))->where('dateTime', '<=', new \DateTime($request->endDate));
        $ids = [];
        foreach ($toDelete as $item) {
            $ids[] = $item->id;
        }

        Calendar::whereIn('id', $ids)->delete();
        return response()->json(["message" => "deleted"], 200);
    }

    function createNote(Request $request)
    {
        if (Auth::user()->isClient()) {
            $notes = Notes::all()->where('client', '==', Auth::user()->id);
            if ($notes != null) {
                foreach ($notes as $note) {
                    if ($note->visited == false && $note->calendar()->dateTime > new \DateTime("now", new \DateTimeZone('Asia/Yekaterinburg'))) {
                        return response()->json(["message" => "Client has unvisited tickets"], 403);
                    }
                }
            }

            $calendar = Calendar::all()->where('id', '==', $request->id)->first();
            $calendar->free = false;
            $calendar->save();
            Notes::create([
                "client" => Auth::user()->id,
                "calendar" => $request->id,
                "visited" => false
            ]);
            return response()->json(["message" => "created"], 201);
        } else {
            $calendar = Calendar::all()->where('id', '==', $request->id)->first();
            if (!$calendar->free) {
                return response()->json(["message" => "taked"], 400);
            }
            $calendar->free = false;
            $calendar->save();
            Notes::create([
                "client" => $request->userID,
                "calendar" => $request->id,
                "visited" => false
            ]);
            return response()->json(["message" => "created"], 201);
        }
    }

    function getNote(Request $request)
    {
        if (Auth::user()->isClient()) {
            $note = Auth::user()->notes()->where('id', '==', $request->id)->first();
            if ($note == null) {
                return response()->json(["message" => "you don't have permission to get our tickets"], 403);
            }
            $calendar = $note->calendar();
            $doctor = $calendar->doctor();
            return response()->json([
                "calendar" => $calendar,
                "doctor" => [
                    "name" => $doctor->name,
                    "surname" => $doctor->surname,
                    "second_name" => $doctor->second_name,
                    "speciality" => $doctor->doctor()->speciality
                ],
                "note" => $note,
                "ticket" => Auth::user()->id . " " . $calendar->id . " " . $note->id
            ]);
        } elseif (Auth::user()->isDoctor()) {
            $note = Notes::all()->where('id', '==', $request->note)->first();
            $calendar = $note->calendar();
            $client = $note->client();
            if ($client->id == $request->client && $calendar->id == $request->calendar) {
                $note->visited = true;
                $note->save();
                $time = new \DateTime($calendar->dateTime);
                $ansewer = [
                    "name" => $client->name,
                    "surname" => $client->surname,
                    "second_name" => $client->second_name,
                    "time" => $time->format("d-m-Y H:i")
                ];
                return response()->json($ansewer, 200);
            }

            return response()->json($request, 400);
        } else {
            $note = Notes::all()->where('id', '==', $request->id)->get()->first();
            $calendar = $note->calendar();
            $doctor = $calendar->doctor();
            return response()->json([
                "calendar" => $calendar,
                "doctor" => [
                    "name" => $doctor->name,
                    "surname" => $doctor->surname,
                    "second_name" => $doctor->second_name,
                    "speciality" => $doctor->doctor()->speciality
                ],
                "note" => $note,
                "ticket" => Auth::user()->id . " " . $calendar->id . " " . $note->id
            ]);
        }
    }

    function getNotes(Request $request)
    {
        $notes = Notes::all()->where('client', '==', $request->id);
        $notes = $notes->sortByDesc('id');
        $answer = [];
        foreach ($notes as $note) {
            $calendar = $note->calendar();
            $doctor = $calendar->doctor();
            $answer[] = ["calendar" => $calendar->dateTime->format("d-m-Y H:i"),
                "doctor" => [
                    "name" => $doctor->name,
                    "surname" => $doctor->surname,
                    "second_name" => $doctor->second_name,
                    "speciality" => $doctor->doctor()->speciality
                ],
                "note" => $note,
                "ticket" => $request->id . " " . $calendar->id . " " . $note->id];
        }
        return response()->json(array_merge($answer), 200);
    }

    function cancelNote(Request $request)
    {
        $note = Notes::all()->where('id', '==', $request->id)->first();
        $calendar = $note->calendar();
        $calendar->free = true;
        $calendar->save();
        $note->delete();
        return response()->json(["message" => "deleted"], 200);
    }

}
