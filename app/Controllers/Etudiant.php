<?php

namespace App\Controllers;

class Formation extends BaseController
{
   public function getAll()
   {
       $model = new \App\Models\FormationModel();
       $data['formations'] = $model->findAll();
       
       return $this->response->setJSON($data);
   }
   public function getById($id)
   {
       $model = new \App\Models\EtudiantModel();
       $data['etudiant'] = $model->find($id);
       if (!$data['etudiant']) {
           throw new \CodeIgniter\Exceptions\PageNotFoundException("Etudiant with ID $id not found");
       }
       return view('etudiant_detail', $data);
   }
}
