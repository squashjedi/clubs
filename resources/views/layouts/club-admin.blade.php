<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}" class="">
    <head>
        @include("partials.head")
    </head>
    <body class="">

        <flux:modal.trigger name="edit-profile">
    <flux:button>Edit profile</flux:button>
</flux:modal.trigger>

<flux:modal name="edit-profile" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Update profile</flux:heading>
            <flux:text class="mt-2">Make changes to your personal details.</flux:text>
        </div>

        <flux:input label="Name" placeholder="Your name" />

        <flux:input label="Date of birth" type="date" />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary">Save changes</flux:button>
        </div>
    </div>
</flux:modal>

        @persist("toast")
            <flux:toast.group position="top end">
                <flux:toast class="!w-76" />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
