<?php

function ModuleAction_datacore_default($params)
{
		
	$s1=new Study(1);
	EngineCore::AddPageContent(EngineCore::VarDumpString($s1));
}

function ModuleAction_datacore_plot($params)
{
	$dp=new DataSeries(1);
	$plotter=new Plotter($dp,640,480);
	$plotter->OutputGraph();
}

function ModuleAction_datacore_series($params)
{
	
}

function ModuleAction_datacore_test($params)
{
	$dp=new DataSeries(1);
	EngineCore::AddPageContent(EngineCore::VarDumpString($dp));
	EngineCore::AddPageContent(EngineCore::VarDumpString($dp->GetValues()));
	EngineCore::AddPageContent("<img src=\"/datacore/plot\" />");
}
