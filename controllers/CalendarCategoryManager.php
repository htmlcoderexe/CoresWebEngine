<?php

namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Models\Calendar\Event;
use PostRoute;
use Route;

/**
 * Description of CalendarCategoryManager
 *
 */
class CalendarCategoryManager
{
    #[Route('calendar/categories','calendar.category.view')]
    public static function ShowCategories()
    {
        return ['entity_type'=>'calendar/categorylist',
            'types'=>Event::GetEventTypes(true)];
    }
    #[Route('calendar/category/edit','calendar.category.edit')]
    public static function ShowEditor($id=0)
    {
        $id = intval($id);
        $q_type = DBHelper::Select(Event::TABLE_TYPES,["id","name","number_colour","marker_colour","agenda_colour","bg_colour","priority","ghost"],['id'=>$id]);
        $type = DBHelper::RunRow($q_type,[$id]);
        if(!$type)
        {
            return EngineCore::Error(404, "Event category does not exist");
        }
        $e = (array)$type;
        $e['entity_type']='calendar/editcategory';
        return $e;
    }
    #[Route('calendar/category/create','calendar.category.edit')]
    public static function ShowCreator()
    {
        $e = ['entity_type'=>'calendar/editcategory'];
        return $e;
    }
    
    #[PostRoute('calendar/category/save','calendar.category.edit')]
    public static function CreateOrUpdateCategory()
    {
        $tagname=EngineCore::POST("name");
        $tagcolour=EngineCore::POST("tagcolour");
        $schedulecolour=EngineCore::POST("agendacolour");
        $bgcolour=EngineCore::POST("bgcolour");
        $numcolour=EngineCore::POST("numcolour");
        $id=intval(EngineCore::POST("TypeID"));
        if($name=="")
        {
            $name="No name";
        }
        if($id==-1)
        {
            DBHelper::Insert(Event::TABLE_TYPES,[null,$tagname,$tagcolour,$schedulecolour,$numcolour,$bgcolour,0,0]);
        }
        else
        {
            DBHelper::Update(Event::TABLE_TYPES,['name'=>$tagname,'number_colour'=>$numcolour,'marker_colour'=>$tagcolour,'agenda_colour'=>$schedulecolour,'bg_colour'=>$bgcolour],['id'=>$id]);
        }
        EngineCore::GTFO('/calendar/categories');
    }
}
