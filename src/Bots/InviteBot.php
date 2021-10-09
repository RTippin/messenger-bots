<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Invite;
use Throwable;

class InviteBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'invitations',
            'description' => 'Generates a short-lived group invitation code and link.',
            'name' => 'Invite Generator',
            'unique' => true,
            'triggers' => ['!invite', '!inv'],
            'match' => MessengerBots::MATCH_EXACT_CASELESS,
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'lifetime_minutes' => ['nullable', 'integer', 'between:5,60'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if (! $this->checkCanGenerateInvite()) {
            $this->releaseCooldown();

            return;
        }

        $expireMinutes = $this->getPayload('lifetime_minutes') ?? 15;
        $invite = $this->generateInvite($expireMinutes);

        $this->sendSuccessMessage($invite, $expireMinutes);
    }

    /**
     * @param  Invite  $invite
     * @param  int  $expireMinutes
     *
     * @throws Throwable
     */
    private function sendSuccessMessage(Invite $invite, int $expireMinutes): void
    {
        $this->composer()->emitTyping()->message(
            "Invite is good for $expireMinutes minutes: $invite->code",
            $this->message->id
        );

        if (! is_null($invite->getInvitationRoute())) {
            $this->composer()->message(":link: {$invite->getInvitationRoute()}");
        }
    }

    /**
     * @return bool
     *
     * @throws Throwable
     */
    private function checkCanGenerateInvite(): bool
    {
        if (! $this->thread->hasInvitationsFeature()) {
            $this->composer()->emitTyping()->message('Invites are currently disabled.', $this->message->id);

            return false;
        }

        if (Messenger::getThreadMaxInvitesCount() !== 0
            && Messenger::getThreadMaxInvitesCount() <= $this->thread->invites()->valid()->count()) {
            $this->composer()->emitTyping()->message('There are too many active invites.', $this->message->id);

            return false;
        }

        return true;
    }

    /**
     * @param  int  $expireMinutes
     * @return Invite
     */
    private function generateInvite(int $expireMinutes): Invite
    {
        return $this->thread->invites()->create([
            'owner_id' => $this->bot->getKey(),
            'owner_type' => $this->bot->getMorphClass(),
            'code' => Str::upper(Str::random(10)).'BOT',
            'max_use' => 0,
            'uses' => 0,
            'expires_at' => now()->addMinutes($expireMinutes),
        ]);
    }
}
