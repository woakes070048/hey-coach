<?php
namespace App\Repositories\Athlete;

use App\Models\Athlete;
use App\Models\Industry;
use App\Models\Invoice;
use App\Models\User;
use DB;
/**
 * Class AthleteRepository
 * @package App\Repositories\Athlete
 */
class AthleteRepository implements AthleteRepositoryContract
{
    const CREATED = 'created';
    const UPDATED_ASSIGN = 'updated_assign';

    /**
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        return Athlete::findOrFail($id);
    }

    /**
     * @return mixed
     */
    public function listAllAthletes()
    {
        return Athlete::pluck('name', 'id');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getInvoices($id)
    {
        $invoice = Athlete::findOrFail($id)->invoices()->with('invoiceLines')->get();

        return $invoice;
    }

    /**
     * @return int
     */
    public function getAllAthletesCount()
    {
        return Athlete::all()->count();
    }

    /**
     * @return mixed
     */
    public function listAllIndustries()
    {
        return Industry::pluck('name', 'id');
    }

    /**
     * @param $requestData
     */
    public function create($requestData)
    {
        $athlete = Athlete::create($requestData);
        Session()->flash('flash_message', 'Athlete successfully added');
    //    event(new \App\Events\AthleteAction($athlete, self::CREATED));
    }

    /**
     * @param $id
     * @param $requestData
     */
    public function update($id, $requestData)
    {
        $athlete = Athlete::findOrFail($id);
        $athlete->fill($requestData->all())->save();
    }

    /**
     * @param $id
     */
    public function destroy($id)
    {
        try {
            $athlete = Athlete::findorFail($id);
            $athlete->delete();
            Session()->flash('flash_message', 'Athlete successfully deleted');
        } catch (\Illuminate\Database\QueryException $e) {
            Session()->flash('flash_message_warning', 'Athlete can NOT have, recruits, or tasks assigned when deleted');
        }
    }

    /**
     * @param $id
     * @param $requestData
     */
    public function updateAssign($id, $requestData)
    {
        $athlete = Athlete::with('user')->findOrFail($id);
        $athlete->user_id = $requestData->get('user_assigned_id');
        $athlete->save();

        event(new \App\Events\AthleteAction($athlete, self::UPDATED_ASSIGN));
    }

    /**
     * @param $requestData
     * @return string
     */
    public function vat($requestData)
    {
        $vat = $requestData->input('vat');

        $country = $requestData->input('country');
        $company_name = $requestData->input('company_name');

        // Strip all other characters than numbers
        $vat = preg_replace('/[^0-9]/', '', $vat);

        function cvrApi($vat)
        {
            if (empty($vat)) {
                // Print error message
                return ('Please insert VAT');
            } else {
                // Start cURL
                $ch = curl_init();

                // Set cURL options
                curl_setopt($ch, CURLOPT_URL, 'http://cvrapi.dk/api?search=' . $vat . '&country=dk');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Flashpoint');

                // Parse result
                $result = curl_exec($ch);

                // Close connection when done
                curl_close($ch);

                // Return our decoded result
                return json_decode($result, 1);
            }
        }

        $result = cvrApi($vat, 'dk');

        return $result;
    }
}
