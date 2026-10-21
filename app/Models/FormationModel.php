<?php 

namespace App\Models;

use CodeIgniter\Model;

class FormationModel extends Model
{
    protected $table = 'formations';
    protected $primaryKey = 'id';
    protected $allowedFields = ['titre', 'description', 'niveau'];
}