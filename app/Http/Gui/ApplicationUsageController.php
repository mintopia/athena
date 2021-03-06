<?php namespace Zeropingheroes\Lanager\Http\Gui;

use Zeropingheroes\Lanager\Domain\BaseResourceService;
use Zeropingheroes\Lanager\Domain\ApplicationUsage\ApplicationUsageService;
use View;

class ApplicationUsageController extends ResourceServiceController {

	protected $applicationUsage;

	/**
	 * Set the controller's service
	 */
	public function __construct()
	{
		$this->applicationUsage = new ApplicationUsageService;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$applications = $this->applicationUsage->userTotalsAt( time() );

		return View::make('application-usage.index')
					->with('title', 'Games Being Played')
					->with('applications', $applications);
	}

}
