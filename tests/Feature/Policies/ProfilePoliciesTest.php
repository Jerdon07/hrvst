<?php

use App\Models\Vegetable\VegetableWatch;
use App\Policies\Profiles\DealerPolicy;
use App\Policies\Profiles\FarmerPolicy;
use App\Policies\Vegetable\VegetableWatchPolicy;

describe('FarmerPolicy', function () {
    it('allows a farmer to update their own profile', function () {
        $farmer = createFarmerUser();

        expect((new FarmerPolicy)->update($farmer, $farmer->farmerProfile))->toBeTrue();
    });

    it('denies a farmer updating another farmer\'s profile', function () {
        expect((new FarmerPolicy)->update(createFarmerUser(), createFarmerUser()->farmerProfile))->toBeFalse();
    });

    it('allows an admin to update any farmer profile', function () {
        expect((new FarmerPolicy)->update(createAdminUser(), createFarmerUser()->farmerProfile))->toBeTrue();
    });

    it('denies a dealer updating a farmer profile', function () {
        expect((new FarmerPolicy)->update(createDealerUser(), createFarmerUser()->farmerProfile))->toBeFalse();
    });

    it('only allows admins to restore', function () {
        expect((new FarmerPolicy)->restore(createAdminUser()))->toBeTrue()
            ->and((new FarmerPolicy)->restore(createFarmerUser()))->toBeFalse();
    });
});

describe('DealerPolicy', function () {
    it('allows a dealer to update their own profile', function () {
        $dealer = createDealerUser();

        expect((new DealerPolicy)->update($dealer, $dealer->dealerProfile))->toBeTrue();
    });

    it('denies a dealer updating another dealer\'s profile', function () {
        expect((new DealerPolicy)->update(createDealerUser(), createDealerUser()->dealerProfile))->toBeFalse();
    });

    it('allows an admin to update any dealer profile', function () {
        expect((new DealerPolicy)->update(createAdminUser(), createDealerUser()->dealerProfile))->toBeTrue();
    });
});

describe('VegetableWatchPolicy', function () {
    it('allows a farmer to create a watch', function () {
        expect((new VegetableWatchPolicy)->create(createFarmerUser()))->toBeTrue();
    });

    it('allows a dealer to create a watch', function () {
        expect((new VegetableWatchPolicy)->create(createDealerUser()))->toBeTrue();
    });

    it('denies a user with neither a farmer nor dealer profile', function () {
        expect((new VegetableWatchPolicy)->create(createAdminUser()))->toBeFalse();
    });

    it('allows the owning user to delete their watch', function () {
        $farmer = createFarmerUser();
        $watch = VegetableWatch::create(['user_id' => $farmer->id, 'vegetable_id' => createVegetable()->id, 'viewer_role' => 'farmer']);

        expect((new VegetableWatchPolicy)->delete($farmer, $watch))->toBeTrue();
    });

    it('denies deleting another user\'s watch', function () {
        $owner = createFarmerUser();
        $intruder = createFarmerUser();
        $watch = VegetableWatch::create(['user_id' => $owner->id, 'vegetable_id' => createVegetable()->id, 'viewer_role' => 'farmer']);

        expect((new VegetableWatchPolicy)->delete($intruder, $watch))->toBeFalse();
    });
});
