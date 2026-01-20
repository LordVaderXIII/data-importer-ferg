@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Authenticate with Basiq') }}</div>

                <div class="card-body">
                    <p>Please provide your Basiq API Key.</p>

                    <form method="POST" action="{{ route('basiq-authenticate.post', ['identifier' => $importJob->identifier]) }}">
                        @csrf

                        <div class="form-group">
                            <label for="api_key">API Key</label>
                            <input type="password" class="form-control" id="api_key" name="api_key" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Save & Continue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
