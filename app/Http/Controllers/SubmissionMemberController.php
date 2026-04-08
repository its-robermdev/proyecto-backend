<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionMemberRequest;
use App\Http\Requests\UpdateSubmissionMemberRequest;
use App\Models\SubmissionMember;

class SubmissionMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Endpoint reservado para listado de miembros por submission.
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // No se usa en API REST (placeholder heredado del scaffold).
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubmissionMemberRequest $request)
    {
        // Endpoint reservado para crear miembros manualmente.
    }

    /**
     * Display the specified resource.
     */
    public function show(SubmissionMember $submissionMember)
    {
        // Endpoint reservado para ver detalle de un miembro.
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubmissionMember $submissionMember)
    {
        // No se usa en API REST (placeholder heredado del scaffold).
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubmissionMemberRequest $request, SubmissionMember $submissionMember)
    {
        // Endpoint reservado para actualizar miembro de submission.
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubmissionMember $submissionMember)
    {
        // Endpoint reservado para eliminar miembro de submission.
    }
}
