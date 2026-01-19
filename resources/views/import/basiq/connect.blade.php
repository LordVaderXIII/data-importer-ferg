@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Connect Basiq Accounts') }}</div>

                <div class="card-body">
                    @if($userId)
                        <div class="alert alert-success">
                            Basiq User ID linked: {{ $userId }}
                        </div>

                        @if(count($connections) > 0)
                            <h5>Existing Accounts:</h5>
                            <ul>
                                @foreach($connections as $account)
                                    <li>{{ $account['name'] ?? 'Unknown' }} ({{ $account['accountNo'] ?? 'N/A' }})</li>
                                @endforeach
                            </ul>
                        @else
                            <p>No connected accounts found.</p>
                        @endif

                        <hr>
                        <p>Do you want to add a new connection (Link a bank)?</p>
                    @else
                        <p>No Basiq user linked yet. Click below to create a user and link your bank.</p>
                    @endif

                    <form method="POST" action="{{ route('basiq-connect.post', ['identifier' => $importJob->identifier]) }}">
                        @csrf

                        <div class="form-group">
                            <label for="mobile">Mobile Number (Optional, for SMS verification)</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" placeholder="+614...">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            {{ $userId ? 'Add Another Bank Connection' : 'Create User & Link Bank' }}
                        </button>

                        @if($userId && count($connections) > 0)
                            <a href="{{ route('configure-import.index', ['identifier' => $importJob->identifier]) }}" class="btn btn-success float-right">
                                Continue with existing connections
                            </a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
