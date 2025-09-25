import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { type Lead, type LeadHistory } from '@/types';
import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';
import dayjs from 'dayjs';
import { Mail, MapPin, Phone, Star, User2, CalendarDays, Clock, GitCommit, Handshake, SquareStack, FileStack, Headset, Calendar, Download, Edit2, Trash2, MoreVertical } from 'lucide-react';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger, } from '@/components/ui/accordion';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface LeadPageProps {
  leadId: number;
}

export default function LeadPage({ leadId }: LeadPageProps) {
  const [lead, setLead] = useState<Lead | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Dialog states
  const [editDialogOpen, setEditDialogOpen] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [selectedMeeting, setSelectedMeeting] = useState<any>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Edit form state
  const [editFormData, setEditFormData] = useState({
    meeting_start_time: '',
    meeting_end_time: '',
  });

  const storageUrl = 'https://sales-aryventory.s3.ap-south-1.amazonaws.com/'

  useEffect(() => {
    const fetchLead = async () => {
      setLoading(true);
      setError(null);
      try {
        const res = await axios.get(`/api/leads/${leadId}`);
        setLead(res.data as Lead);
      } catch (e) {
        setError('Failed to load lead');
      } finally {
        setLoading(false);
      }
    };
    fetchLead();
  }, [leadId]);

  const sortedHistories: LeadHistory[] = useMemo(() => {
    const items = (lead as any)?.histories ?? [];
    return [...items].sort((a: LeadHistory, b: LeadHistory) => {
      const at = a.timestamp ? dayjs(a.timestamp).valueOf() : 0;
      const bt = b.timestamp ? dayjs(b.timestamp).valueOf() : 0;
      return bt - at;
    });
  }, [lead]);


  const sortedMeetings = useMemo(() => {
    const items = lead?.meetings ?? [];
    return [...items].sort((a, b) => {
      const at = a.meeting_start_time ? dayjs(a.meeting_start_time).valueOf() : 0;
      const bt = b.meeting_start_time ? dayjs(b.meeting_start_time).valueOf() : 0;
      return bt - at; // latest first
    });
  }, [lead]);

  const renderRating = (rating?: number) => {
    if (!rating) return '-';
    return (
      <div className="flex items-center gap-1">
        {Array.from({ length: 5 }).map((_, i) => (
          <Star key={i} className={`h-3 w-3 ${i < rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'}`} />
        ))}
        <span className="text-xs ml-1">({rating})</span>
      </div>
    );
  };

  const formatDate = (value?: string | null) => (value ? dayjs(value).format('DD MMM YYYY') : '-');
  const formatDateTime = (value?: string | null) => (value ? dayjs(value).format('DD MMM YYYY, HH:mm') : '-');

  // Handle opening edit dialog
  const handleEditMeeting = (meeting: any) => {
    setSelectedMeeting(meeting);
    setEditFormData({
      meeting_start_time: meeting.meeting_start_time ? dayjs(meeting.meeting_start_time).format('YYYY-MM-DDTHH:mm') : '',
      meeting_end_time: meeting.meeting_end_time ? dayjs(meeting.meeting_end_time).format('YYYY-MM-DDTHH:mm') : '',
    });
    setEditDialogOpen(true);
  };

  // Handle opening delete dialog
  const handleDeleteMeeting = (meeting: any) => {
    setSelectedMeeting(meeting);
    setDeleteDialogOpen(true);
  };

  // Handle edit meeting submission
  const handleEditSubmit = async () => {
    if (!selectedMeeting) return;
    
    setIsSubmitting(true);
    try {
      await axios.put(`/api/meetings/${selectedMeeting.id}`, {
        meeting_start_time: editFormData.meeting_start_time,
        meeting_end_time: editFormData.meeting_end_time || null,
      });
      
      // Refresh lead data
      const res = await axios.get(`/api/leads/${leadId}`);
      setLead(res.data as Lead);
      
      setEditDialogOpen(false);
      setSelectedMeeting(null);
    } catch (error) {
      console.error('Failed to update meeting:', error);
      setError('Failed to update meeting');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Handle delete meeting submission
  const handleDeleteSubmit = async () => {
    if (!selectedMeeting) return;
    
    setIsSubmitting(true);
    try {
      await axios.delete(`/api/meetings/${selectedMeeting.id}`);
      
      // Refresh lead data
      const res = await axios.get(`/api/leads/${leadId}`);
      setLead(res.data as Lead);
      
      setDeleteDialogOpen(false);
      setSelectedMeeting(null);
    } catch (error) {
      console.error('Failed to delete meeting:', error);
      setError('Failed to delete meeting');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Leads', href: '/leads' },
        { title: lead ? lead.shop_name : 'Lead', href: `/leads/${leadId}` },
      ]}
    >
      <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
        <Heading title={lead ? lead.shop_name : 'Lead Details'} />

        {loading && (
          <div className="text-muted-foreground">Loading...</div>
        )}

        {error && (
          <div className="text-destructive">{error}</div>
        )}

        {!loading && lead && (
          <div className="grid gap-4 md:grid-cols-4">

            <div className="space-y-4 md:col-span-2">
              <Card>
                <CardHeader>
                  <CardTitle>Lead Information</CardTitle>
                  <CardDescription>Overview of this lead</CardDescription>
                </CardHeader>
                <CardContent>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                      <div className="text-sm text-muted-foreground">Contact Person</div>
                      <div className="flex items-center gap-2"><User2 className="h-4 w-4" />{lead.contact_person}</div>
                    </div>
                    <div>
                      <div className="text-sm text-muted-foreground">Created On</div>
                      <div>{formatDate(lead.created_at)}</div>
                    </div>
                    <div>
                      <div className="text-sm text-muted-foreground">Mobile</div>
                      <div className="flex items-center gap-2"><Phone className="h-4 w-4" />{lead.mobile_number}</div>
                    </div>
                    <div>
                      <div className="text-sm text-muted-foreground">Email</div>
                      <div className="flex items-center gap-2">{lead.email ? (<><Mail className="h-4 w-4" />{lead.email}</>) : '-'}</div>
                    </div>
                    <div className="sm:col-span-2">
                      <div className="text-sm text-muted-foreground">Address</div>
                      <div className="flex items-center gap-2">{lead.address ? (<><MapPin className="h-4 w-4" />{lead.address}</>) : '-'}</div>
                    </div>
                    <div className="sm:col-span-2">
                      <div className="text-sm text-muted-foreground">Branches</div>
                      <div className="flex items-center gap-2">{lead.branches ? (<><MapPin className="h-4 w-4" />{lead.branches}</>) : '-'}</div>
                    </div>
                    <div className="sm:col-span-2">
                      <div className="text-sm text-muted-foreground">GPS Location</div>
                      <div className="flex items-center gap-2">{lead.gps_location ? (<><MapPin className="h-4 w-4" />{lead.gps_location}</>) : '-'}</div>
                    </div>
                  </div>

                  <Separator className="my-4" />
                  <div className="grid gap-4 sm:grid-cols-3">
                    <div>
                      <div className="text-sm text-muted-foreground">Status</div>
                      <div>
                        {lead.lead_status_data ? (
                          <Badge variant="outline">{lead.lead_status_data.name}</Badge>
                        ) : '-'}
                      </div>
                    </div>
                    <div>
                      <div className="text-sm text-muted-foreground">Assigned To</div>
                      <div>
                        {lead.assigned_to_user ? (
                          <Badge className="bg-blue-500" variant="secondary">{lead.assigned_to_user.name}</Badge>
                        ) : 'Unassigned'}
                      </div>
                    </div>
                    <div>
                      <div className="text-sm text-muted-foreground">Next Follow-up</div>
                      <div className="flex items-center gap-2"><Clock className="h-4 w-4" />{formatDate(lead.next_follow_up_date)}</div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Meeting Notes</CardTitle>
                  <CardDescription>Notes captured during meetings</CardDescription>
                </CardHeader>
                <CardContent>
                  {lead.meeting_notes ? (
                    <div className="whitespace-pre-wrap leading-relaxed">{lead.meeting_notes}</div>
                  ) : (
                    <div className="text-muted-foreground">No meeting notes available.</div>
                  )}
                </CardContent>
              </Card>
            </div>

            <div className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <CalendarDays /> Meetings
                  </CardTitle>
                  <CardDescription>Lead meetings with media</CardDescription>
                </CardHeader>
                <CardContent>
                  {sortedMeetings.length === 0 && (
                    <div className="text-muted-foreground">No meetings yet.</div>
                  )}

                  <Accordion type="single" collapsible className="w-full">
                    {sortedMeetings.map((m, idx) => (
                      <AccordionItem key={m.id ?? idx} value={`meeting-${m.id}`}>
                        <AccordionTrigger>
                          <div className="flex flex-1 items-center justify-between">
                            <div className="flex flex-col text-left">
                              <span className="text-sm font-medium">
                                Meeting #{m.id} — {formatDateTime(m.meeting_start_time)}
                              </span>
                              <span className="text-xs text-muted-foreground flex items-center gap-1 mt-2">
                                <Clock size={12} /> {formatDateTime(m.meeting_start_time)} → {formatDateTime(m.meeting_end_time)}
                              </span>
                            </div>
                            <div className="mr-2" onClick={(e) => e.stopPropagation()}>
                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                    <MoreVertical className="h-4 w-4" />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                  <DropdownMenuItem onClick={() => handleEditMeeting(m)}>
                                    <Edit2 className="mr-2 h-4 w-4" />
                                    Edit
                                  </DropdownMenuItem>
                                  <DropdownMenuItem 
                                    onClick={() => handleDeleteMeeting(m)}
                                    className="text-red-600 focus:text-red-600"
                                  >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Delete
                                  </DropdownMenuItem>
                                </DropdownMenuContent>
                              </DropdownMenu>
                            </div>
                          </div>
                        </AccordionTrigger>
                        <AccordionContent>
                          <div className="space-y-4 pt-2">

                            {/* Recorded Audios */}
                            {m.recorded_audios?.length > 0 && (
                              <div>
                                <div className="font-medium text-sm mb-1">Recorded Audios:</div>
                                <ul className="list-disc list-inside text-sm">
                                  {m.recorded_audios.map((audio: any) => {
                                    const ext = audio.media.split('.').pop()?.toLowerCase();

                                    // If it's 3gp (less supported), show a download link fallback
                                    if (ext === '3gp') {
                                      return (
                                        <li key={audio.id}>
                                          <div>
                                            <p>Audio (3gp format, may not play in all browsers):</p>
                                            <a href={storageUrl + audio.media} download target="_blank" rel="noopener noreferrer" className="text-lime-600 underline flex items-center gap-1">
                                             <Download size={12} /> Download audio
                                            </a>
                                          </div>
                                        </li>
                                      );
                                    }

                                    // For mp3 and other supported formats, use audio player
                                    return (
                                      <li key={audio.id}>
                                        <audio controls src={storageUrl + audio.media} className="mt-1" />
                                      </li>
                                    );
                                  })}
                                </ul>
                              </div>
                            )}

                            {/* Selfies */}
                            {m.selfies?.length > 0 && (
                              <div>
                                <div className="font-medium text-sm mb-1">Selfies:</div>
                                <div className="flex gap-2 flex-wrap">
                                  {m.selfies.map((selfie: any) => (
                                    <img
                                      key={selfie.id}
                                      src={storageUrl + selfie.media}
                                      alt="Selfie"
                                      className="w-20 h-20 rounded object-cover"
                                    />
                                  ))}
                                </div>
                              </div>
                            )}

                            {/* Shop Photos */}
                            {m.shop_photos?.length > 0 && (
                              <div>
                                <div className="font-medium text-sm mb-1">Shop Photos:</div>
                                <div className="flex gap-2 flex-wrap">
                                  {m.shop_photos.map((photo: any) => (
                                    <img
                                      key={photo.id}
                                      src={storageUrl + photo.media}
                                      alt="Shop"
                                      className="w-20 h-20 rounded object-cover"
                                    />
                                  ))}
                                </div>
                              </div>
                            )}

                          </div>
                        </AccordionContent>
                      </AccordionItem>
                    ))}
                  </Accordion>
                </CardContent>
              </Card>
            </div>



            <div className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle className='flex items-center gap-2'><FileStack /> Lead History</CardTitle>
                  <CardDescription>Lead status updates</CardDescription>
                </CardHeader>
                <CardContent>
                  {sortedHistories.length === 0 && (
                    <div className="text-muted-foreground">No history yet.</div>
                  )}
                  <div className="space-y-4">
                    {sortedHistories.map((h, idx) => (
                      <div key={idx} className="flex items-start gap-3">
                        <GitCommit className="h-4 w-4 mt-1 text-muted-foreground" />
                        <div className="flex-1">
                          <div className="text-sm flex items-center gap-2">
                            <span className="text-muted-foreground">{formatDateTime(h.timestamp)}</span>  {h.action && <Badge>{h.action}</Badge>}
                          </div>
                          <div className="text-sm mt-1">
                            {h.status_before || h.status_after ? (
                              <>
                                Status: {h.status_before ?? '-'} → {h.status_after ?? '-'}
                              </>
                            ) : (
                              'Updated'
                            )}
                          </div>
                          {h.notes && (
                            <div className="text-sm text-muted-foreground mt-1">{h.notes}</div>
                          )}
                          {(((h as any).updated_by)?.name || ((h as any).updated_by_user)?.name) && (
                            <div className="text-xs text-muted-foreground mt-1">by {(((h as any).updated_by)?.name) ?? (((h as any).updated_by_user)?.name)}</div>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        )}

        {/* Edit Meeting Dialog */}
        <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
          <DialogContent className="sm:max-w-[425px]">
            <DialogHeader>
              <DialogTitle>Edit Meeting</DialogTitle>
              <DialogDescription>
                Update the meeting start and end times.
              </DialogDescription>
            </DialogHeader>
            <div className="grid gap-4 py-4">
              <div className="grid gap-2">
                <Label htmlFor="meeting_start_time">Meeting Start Time</Label>
                <Input
                  id="meeting_start_time"
                  type="datetime-local"
                  value={editFormData.meeting_start_time}
                  onChange={(e) => setEditFormData({ ...editFormData, meeting_start_time: e.target.value })}
                />
              </div>
              <div className="grid gap-2">
                <Label htmlFor="meeting_end_time">Meeting End Time</Label>
                <Input
                  id="meeting_end_time"
                  type="datetime-local"
                  value={editFormData.meeting_end_time}
                  onChange={(e) => setEditFormData({ ...editFormData, meeting_end_time: e.target.value })}
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setEditDialogOpen(false)} disabled={isSubmitting}>
                Cancel
              </Button>
              <Button onClick={handleEditSubmit} disabled={isSubmitting}>
                {isSubmitting ? 'Saving...' : 'Save Changes'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Delete Meeting Dialog */}
        <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
          <DialogContent className="sm:max-w-[425px]">
            <DialogHeader>
              <DialogTitle>Delete Meeting</DialogTitle>
              <DialogDescription>
                Are you sure you want to delete this meeting? This action cannot be undone and will remove all associated media files.
              </DialogDescription>
            </DialogHeader>
            <div className="py-4">
              {selectedMeeting && (
                <div className="p-4 bg-muted rounded-md">
                  <p className="text-sm font-medium">Meeting #{selectedMeeting.id}</p>
                  <p className="text-sm text-muted-foreground">
                    {formatDateTime(selectedMeeting.meeting_start_time)} → {formatDateTime(selectedMeeting.meeting_end_time)}
                  </p>
                </div>
              )}
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setDeleteDialogOpen(false)} disabled={isSubmitting}>
                Cancel
              </Button>
              <Button variant="destructive" onClick={handleDeleteSubmit} disabled={isSubmitting}>
                {isSubmitting ? 'Deleting...' : 'Delete Meeting'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </AppLayout>
  );
}


