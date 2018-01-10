<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

use Exception;

class Complaint extends Model
{
    protected $table = 'complaints';

    /**
     * All Complaints are assigned a status 
     * @return App::ComplaintStatus 
     */
    public function complaintStatus(){
        return $this->belongsTo('App\ComplaintStatus','status_id');
    }

    /**
     * Each Complaints are made by a User
     * @return App::User
     */
    public function user(){
        return $this->belongsTo('App\User');
    }

    /**
     * By using the params - userID, startDate and endDate, the complaints are retieved by the
     * available combinations of parameters of startDate & endDate.
     * @param  userID    
     * @param  startDate 
     * @param  endDate   
     * @return [array] complaints
     */
     static public function getUserComplaints($startDate, $endDate, $hostel = NULL){
 
        $userID = User::getUserID();
        if(! $userID)
             throw new Exception("user not logged in", 1);  

        $complaints = Complaint::select('id','title','description','image_url','created_at')
                               ->where('user_id',$userID)
                               ->get();
        if(isset($startDate))
            $complaints = $complaints->where('created_at','>=',$startDate);
        if(isset($endDate))
            $complaints = $complaints->where('created_at','<=',$endDate);

        return $complaints->values()->all(); 
    }


     /**
     * This is for the admin GET route. 
     * By using the complaint->user, complaint->status, user->hostel relationships, 
     * the data for the admin's complaint-feed is retrieved.   
     * @param  startDate 
     * @param  endDate
     * @param  hostel
     * @param  status   
     * @return [array] response 
     */
    static public function getAllComplaints($startDate, $endDate, $hostel, $status){
         
        $userID = User::getUserID();
        if(! $userID)
            throw new Exception("user not logged in", 1);
             
        if(! User::isUserAdmin())
            throw new Exception("user not admin", 2);
 
         $complaints = Complaint::select('id','user_id','title','description',
                                        'status_id','image_url','created_at')
                               ->get();

        foreach ($complaints as $complaint) {
            $complaint->status = $complaint->complaintStatus()->select('name','message')->first();
            $complaint->user = $complaint->user()->select('username','name','room_no','hostel_id',
                                                          'phone_contact','whatsapp_contact','email')
                                                 ->first();

            $complaint->user->hostel = $complaint->user->hostel()->value('name');
        }

        if(isset($startDate))
            $complaints = $complaints->where('created_at','>=',$startDate);
        if(isset($endDate))
            $complaints = $complaints->where('created_at','<=',$endDate);
        if(isset($status))
            $complaints = $complaints->filter(function($complaint) use($status){
                return $complaint->status->name == $status;
            });
        if(isset($hostel))
            $complaints = $complaints->filter(function($complaint) use($hostel){
                return $complaint->user->hostel == $hostel;
            });
        
        return $complaints->values()->all();
    }

    /**
     * This is for the user DELETE route. 
     * By using the complaint id,  
     * the instance of the table with given id is deleted
     * @param id
     * @param userID
     * @return 1 for sucessfully created and 0 if not
    */
   
     static public function deleteComplaint($id){
        
         $userID = User::getUserID();
         $isUserAdmin = User::isUserAdmin();
     
         if(! $userID)
             throw new Exception("user not logged in", 1);
             
         if(! $isUserAdmin)
             throw new Exception("user not admin", 2);
     
        if(!Complaint::where('id',$id)->exists())        
              throw new Exception("complaint doesn't exist",3); 
        
         Complaint::where('id',$id)->delete();
         
         $idList = ComplaintComment::where('complaint_id',$id)->pluck('id');
         
         ComplaintComment::where('complaint_id',$id)->delete();

         foreach($idList as $Id){
            ComplaintReply::where('parent_id',$Id)->delete();
         }
     } 
}






















